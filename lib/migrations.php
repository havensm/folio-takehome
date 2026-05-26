<?php

function apply_migrations(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS schema_migrations (
            filename TEXT PRIMARY KEY,
            applied_at TEXT NOT NULL DEFAULT (datetime('now'))
        )
    ");

    $files = glob(__DIR__ . '/../migrations/*.sql') ?: [];
    sort($files, SORT_STRING);

    $appliedStmt = $pdo->prepare('SELECT 1 FROM schema_migrations WHERE filename = ?');
    $recordStmt = $pdo->prepare('INSERT INTO schema_migrations (filename) VALUES (?)');

    foreach ($files as $file) {
        $filename = basename($file);
        $appliedStmt->execute([$filename]);
        if ($appliedStmt->fetchColumn()) {
            continue;
        }

        $pdo->beginTransaction();
        try {
            $pdo->exec(file_get_contents($file));
            $recordStmt->execute([$filename]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
