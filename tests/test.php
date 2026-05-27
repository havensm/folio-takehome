<?php

require __DIR__ . '/../lib/bootstrap.php';
require __DIR__ . '/../lib/documents.php';

system('php ' . escapeshellarg(__DIR__ . '/../seed.php') . ' > /dev/null', $rc);
if ($rc !== 0) {
    fwrite(STDERR, "seed failed\n");
    exit(1);
}

$pass = 0;
$fail = 0;

function test(string $name, callable $fn): void {
    global $pass, $fail;
    try {
        $fn();
        echo "  [ok] {$name}\n";
        $pass++;
    } catch (Throwable $e) {
        echo "  [FAIL] {$name}: " . $e->getMessage() . "\n";
        $fail++;
    }
}

function assert_true($cond, string $msg = ''): void {
    if (!$cond) {
        throw new RuntimeException($msg !== '' ? $msg : 'expected true');
    }
}

function assert_same($expected, $actual, string $msg = ''): void {
    if ($expected !== $actual) {
        throw new RuntimeException($msg !== '' ? $msg : 'expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
}

function assert_contains(string $needle, string $haystack, string $msg = ''): void {
    if (strpos($haystack, $needle) === false) {
        throw new RuntimeException($msg !== '' ? $msg : 'expected output to contain ' . var_export($needle, true));
    }
}

function assert_not_contains(string $needle, string $haystack, string $msg = ''): void {
    if (strpos($haystack, $needle) !== false) {
        throw new RuntimeException($msg !== '' ? $msg : 'expected output not to contain ' . var_export($needle, true));
    }
}

function run_public_script(string $script, array $get = [], array $post = [], string $method = 'GET'): array {
    $scriptPath = realpath(__DIR__ . '/../public/' . $script);
    if ($scriptPath === false) {
        throw new RuntimeException('missing public script: ' . $script);
    }

    $code = "<?php\n"
        . '$_GET = ' . var_export($get, true) . ";\n"
        . '$_POST = ' . var_export($post, true) . ";\n"
        . '$_REQUEST = array_merge($_GET, $_POST);' . "\n"
        . '$_SERVER["REQUEST_METHOD"] = ' . var_export($method, true) . ";\n"
        . '$_SERVER["HTTP_HOST"] = "localhost:8000";' . "\n"
        . 'include ' . var_export($scriptPath, true) . ";\n";

    $process = proc_open(
        'php',
        [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ],
        $pipes,
        dirname(__DIR__)
    );

    if (!is_resource($process)) {
        throw new RuntimeException('could not start PHP process');
    }

    fwrite($pipes[0], $code);
    fclose($pipes[0]);

    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);

    $exitCode = proc_close($process);

    return [
        'exit_code' => $exitCode,
        'stdout' => $stdout,
        'stderr' => $stderr,
    ];
}

function audit_entry_count(string $action, string $entityType, int $entityId): int {
    $stmt = db()->prepare('
        SELECT COUNT(*)
        FROM audit_log
        WHERE action = ? AND entity_type = ? AND entity_id = ?
    ');
    $stmt->execute([$action, $entityType, $entityId]);

    return (int) $stmt->fetchColumn();
}

echo "\nRunning tests:\n";

test('seeded share link resolves to the seeded document', function () {
    $stmt = db()->prepare('
        SELECT d.title
        FROM shares s
        JOIN documents d ON d.id = s.document_id
        LIMIT 1
    ');
    $stmt->execute();
    $row = $stmt->fetch();
    assert_true($row !== false, 'expected the seeded share to resolve');
    assert_true($row['title'] === 'Welcome Packet', 'unexpected title: ' . var_export($row['title'], true));
});

test('documents get a short readable id that can resolve the document', function () {
    $docId = create_document('Quarterly Roadmap', 'Roadmap body', 1, storage_datetime(app_now()));
    $doc = document_by_identifier((string) $docId);

    assert_true($doc !== null, 'expected document to exist');
    assert_true(
        preg_match('/^quarterly-roadmap-[23456789ABCDEFGHJKLMNPQRSTUVWXYZ]{4}$/', $doc['readable_id']) === 1,
        'unexpected readable id: ' . var_export($doc['readable_id'], true)
    );

    $byReadableId = document_by_identifier(strtolower($doc['readable_id']));
    assert_same($docId, (int) $byReadableId['id'], 'expected readable id lookup to resolve the same document');
});

test('document public identifiers fall back to numeric ids when readable ids are missing', function () {
    $docId = create_document('Legacy Packet', 'Legacy body', 1, storage_datetime(app_now()));
    $stmt = db()->prepare('UPDATE documents SET readable_id = NULL WHERE id = ?');
    $stmt->execute([$docId]);

    $doc = document_by_identifier((string) $docId);

    assert_true($doc !== null, 'expected legacy document to exist');
    assert_same((string) $docId, document_public_identifier($doc), 'expected numeric id fallback');
});

test('scheduled documents are hidden until their publish time', function () {
    $future = storage_datetime(app_now()->modify('+1 day'));
    $docId = create_document('Embargoed Plan', 'Secret until tomorrow', 1, $future);
    $doc = document_by_identifier((string) $docId);

    assert_true(!document_is_published($doc), 'future document should not be published yet');

    $past = storage_datetime(app_now()->modify('-1 minute'));
    update_document_schedule($docId, $past);
    $doc = document_by_identifier((string) $docId);

    assert_true(document_is_published($doc), 'past publish time should make the document visible');
});

test('document search finds share candidates by title substring', function () {
    create_document('Employee Safety Handbook', 'Safety body', 1, storage_datetime(app_now()));
    create_document('Benefits Guide', 'Benefits body', 1, storage_datetime(app_now()));

    $results = search_documents('safety');
    $titles = array_column($results, 'title');

    assert_true(in_array('Employee Safety Handbook', $titles, true), 'expected matching title in search results');
    assert_true(!in_array('Benefits Guide', $titles, true), 'did not expect unrelated title in search results');
});

test('document search treats wildcard characters literally', function () {
    create_document('Literal Percent % Guide', 'Percent body', 1, storage_datetime(app_now()));
    create_document('Plain Guide', 'Plain body', 1, storage_datetime(app_now()));

    $results = search_documents('%');
    $titles = array_column($results, 'title');

    assert_true(in_array('Literal Percent % Guide', $titles, true), 'expected title containing a literal percent sign');
    assert_true(!in_array('Plain Guide', $titles, true), 'wildcard search should not match every title');
});

test('invalid schedule dates are rejected instead of normalized', function () {
    try {
        parse_publish_input('2026-02-31T10:00');
    } catch (InvalidArgumentException $e) {
        assert_true(true);
        return;
    }

    throw new RuntimeException('expected invalid date to be rejected');
});

test('documents can be resolved by readable id for staff review', function () {
    $docId = create_document('Reviewable Document', 'Review body', 1, storage_datetime(app_now()));
    $doc = document_by_identifier((string) $docId);
    $reviewDoc = document_by_identifier($doc['readable_id']);

    assert_same('Reviewable Document', $reviewDoc['title'], 'expected review lookup to find the document');
});

test('staff review tokens are required for document review access', function () {
    $docId = create_document('Restricted Review', 'Review body', 1, storage_datetime(app_now()));
    $doc = document_by_identifier((string) $docId);

    assert_true($doc !== null, 'expected document to exist');
    assert_true(strlen($doc['staff_review_token']) === 32, 'expected generated staff review token');
    assert_true(document_review_token_valid($doc, $doc['staff_review_token']), 'expected matching token to be valid');
    assert_true(!document_review_token_valid($doc, ''), 'expected blank token to be invalid');
    assert_true(!document_review_token_valid($doc, random_token()), 'expected wrong token to be invalid');
});

test('recipient view renders when readable id and token match', function () {
    $stmt = db()->query('
        SELECT d.*, s.token
        FROM shares s
        JOIN documents d ON d.id = s.document_id
        LIMIT 1
    ');
    $share = $stmt->fetch();

    $response = run_public_script('view.php', [
        'id' => $share['readable_id'],
        'token' => $share['token'],
    ]);

    assert_same(0, $response['exit_code'], $response['stderr']);
    assert_contains('Welcome Packet', $response['stdout']);
    assert_contains('Welcome to Folio!', $response['stdout']);
});

test('recipient view rejects a mismatched readable id even with a valid token', function () {
    $stmt = db()->query('
        SELECT d.*, s.token
        FROM shares s
        JOIN documents d ON d.id = s.document_id
        LIMIT 1
    ');
    $share = $stmt->fetch();

    $response = run_public_script('view.php', [
        'id' => 'not-' . $share['readable_id'],
        'token' => $share['token'],
    ]);

    assert_same(0, $response['exit_code'], $response['stderr']);
    assert_contains('Share link not found', $response['stdout']);
    assert_not_contains('Welcome to Folio!', $response['stdout']);
});

test('recipient view hides future scheduled documents through the public route', function () {
    $future = storage_datetime(app_now()->modify('+1 day'));
    $docId = create_document('Future Route Plan', 'Visible later only', 1, $future);
    $doc = document_by_identifier((string) $docId);
    $token = create_share($docId, 'future@example.com');

    $response = run_public_script('view.php', [
        'id' => $doc['readable_id'],
        'token' => $token,
    ]);

    assert_same(0, $response['exit_code'], $response['stderr']);
    assert_contains('Document not yet available', $response['stdout']);
    assert_not_contains('Visible later only', $response['stdout']);
});

test('admin create and schedule routes write audit entries', function () {
    $createResponse = run_public_script('admin.php', [], [
        'action' => 'create_document',
        'title' => 'Audited Route Document',
        'body' => 'Created through the admin route.',
        'published_at' => '',
    ], 'POST');
    assert_same(0, $createResponse['exit_code'], $createResponse['stderr']);

    $stmt = db()->prepare('SELECT * FROM documents WHERE title = ? ORDER BY id DESC LIMIT 1');
    $stmt->execute(['Audited Route Document']);
    $doc = $stmt->fetch();
    unset($stmt);

    assert_true($doc !== false, 'expected admin route to create a document');
    assert_true(audit_entry_count('create', 'document', (int) $doc['id']) >= 1, 'expected document create audit entry');

    $scheduleResponse = run_public_script('admin.php', [], [
        'action' => 'update_schedule',
        'doc' => $doc['readable_id'],
        'published_at' => app_now()->modify('+2 hours')->format('Y-m-d\TH:i'),
    ], 'POST');
    assert_same(0, $scheduleResponse['exit_code'], $scheduleResponse['stderr']);

    assert_true(audit_entry_count('schedule', 'document', (int) $doc['id']) >= 1, 'expected document schedule audit entry');
});

test('share route creates a private share link and audit entry', function () {
    $docId = create_document('Audited Share Document', 'Shared through the route.', 1, storage_datetime(app_now()));
    $doc = document_by_identifier((string) $docId);

    $response = run_public_script('share.php', [
        'doc' => $doc['readable_id'],
    ], [
        'email' => 'audited-share@example.com',
    ], 'POST');

    assert_same(0, $response['exit_code'], $response['stderr']);
    assert_contains('Share link ready', $response['stdout']);
    assert_contains('/view.php?id=', $response['stdout']);

    $stmt = db()->prepare('SELECT * FROM shares WHERE document_id = ? AND recipient_email = ? ORDER BY id DESC LIMIT 1');
    $stmt->execute([$docId, 'audited-share@example.com']);
    $share = $stmt->fetch();

    assert_true($share !== false, 'expected share route to create a share');
    assert_true(audit_entry_count('create', 'share', (int) $share['id']) >= 1, 'expected share create audit entry');
});

echo "\n{$pass} passed, {$fail} failed.\n";
exit($fail > 0 ? 1 : 0);
