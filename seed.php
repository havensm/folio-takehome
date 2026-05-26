<?php

require __DIR__ . '/lib/bootstrap.php';
require __DIR__ . '/lib/documents.php';
require __DIR__ . '/lib/migrations.php';

$dbPath = __DIR__ . '/db.sqlite';
if (file_exists($dbPath)) {
    unlink($dbPath);
}

$pdo = db();
$pdo->exec(file_get_contents(__DIR__ . '/schema.sql'));
apply_migrations($pdo);

$pdo->exec("
    INSERT INTO staff (email, name) VALUES
        ('freddy@folio.example', 'Freddy Folio')
");

$docId = create_document(
    'Welcome Packet',
    "Welcome to Folio!\n\nThis is the body of your welcome packet.",
    1,
    storage_datetime(app_now())
);

$token = random_token();
$stmt = $pdo->prepare('
    INSERT INTO shares (document_id, token, recipient_email)
    VALUES (?, ?, ?)
');
$stmt->execute([$docId, $token, 'recipient@example.com']);

$doc = document_by_identifier((string) $docId);

echo "Seeded db.sqlite.\n";
echo "Admin:        http://localhost:8000/admin.php\n";
echo "Sample share: http://localhost:8000/view.php?id={$doc['readable_id']}&token={$token}\n";
