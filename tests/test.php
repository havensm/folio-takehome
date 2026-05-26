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

echo "\n{$pass} passed, {$fail} failed.\n";
exit($fail > 0 ? 1 : 0);
