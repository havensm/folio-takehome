<?php

const READABLE_ID_SUFFIX_ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

function app_now(): DateTimeImmutable {
    return new DateTimeImmutable('now', new DateTimeZone(date_default_timezone_get()));
}

function storage_datetime(DateTimeInterface $date): string {
    return $date->format('Y-m-d H:i:s');
}

function parse_publish_input(?string $value): string {
    $value = trim((string) $value);
    if ($value === '') {
        return storage_datetime(app_now());
    }

    $date = DateTimeImmutable::createFromFormat(
        'Y-m-d\TH:i',
        $value,
        new DateTimeZone(date_default_timezone_get())
    );
    $errors = DateTimeImmutable::getLastErrors();

    if (
        !$date
        || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
        || $date->format('Y-m-d\TH:i') !== $value
    ) {
        throw new InvalidArgumentException('Publish date must be a valid date and time.');
    }

    return storage_datetime($date);
}

function format_datetime_local(?string $value): string {
    if ($value === null || trim($value) === '') {
        return '';
    }

    $date = new DateTimeImmutable($value, new DateTimeZone(date_default_timezone_get()));
    return $date->format('Y-m-d\TH:i');
}

function format_display_datetime(?string $value): string {
    if ($value === null || trim($value) === '') {
        return 'now';
    }

    $date = new DateTimeImmutable($value, new DateTimeZone(date_default_timezone_get()));
    return $date->format('M j, Y g:i A T');
}

function document_is_published(array $document, ?DateTimeInterface $now = null): bool {
    $publishedAt = $document['published_at'] ?? null;
    if ($publishedAt === null || trim($publishedAt) === '') {
        return true;
    }

    $now = $now ?: app_now();
    $publishDate = new DateTimeImmutable($publishedAt, new DateTimeZone(date_default_timezone_get()));
    return $publishDate <= $now;
}

function slug_base(string $title): string {
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
    $slug = trim($slug, '-');

    if ($slug === '') {
        $slug = 'document';
    }

    return substr($slug, 0, 32);
}

function readable_suffix(int $length = 4): string {
    $alphabetLength = strlen(READABLE_ID_SUFFIX_ALPHABET);
    $suffix = '';

    for ($i = 0; $i < $length; $i++) {
        $suffix .= READABLE_ID_SUFFIX_ALPHABET[random_int(0, $alphabetLength - 1)];
    }

    return $suffix;
}

function generate_readable_id(string $title): string {
    $base = slug_base($title);
    $stmt = db()->prepare('SELECT 1 FROM documents WHERE readable_id = ?');

    for ($attempt = 0; $attempt < 10; $attempt++) {
        $candidate = $base . '-' . readable_suffix();
        $stmt->execute([$candidate]);
        if (!$stmt->fetchColumn()) {
            return $candidate;
        }
    }

    throw new RuntimeException('Could not generate a unique readable document ID.');
}

function create_document(string $title, string $body, int $staffId, string $publishedAt): int {
    $readableId = generate_readable_id($title);
    $staffReviewToken = random_token();
    $stmt = db()->prepare('
        INSERT INTO documents (title, body, created_by, published_at, readable_id, staff_review_token)
        VALUES (?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([$title, $body, $staffId, $publishedAt, $readableId, $staffReviewToken]);
    return (int) db()->lastInsertId();
}

function document_by_identifier(string $identifier): ?array {
    $identifier = trim($identifier);
    if ($identifier === '') {
        return null;
    }

    if (ctype_digit($identifier)) {
        $stmt = db()->prepare('SELECT * FROM documents WHERE id = ?');
    } else {
        $stmt = db()->prepare('SELECT * FROM documents WHERE readable_id = ? COLLATE NOCASE');
    }

    $stmt->execute([$identifier]);
    $document = $stmt->fetch();

    return $document ?: null;
}

function document_public_identifier(array $document): string {
    $readableId = trim((string) ($document['readable_id'] ?? ''));

    return $readableId !== '' ? $readableId : (string) $document['id'];
}

function document_identifier_matches(array $document, string $identifier): bool {
    $identifier = trim($identifier);
    if ($identifier === '') {
        return false;
    }

    if (ctype_digit($identifier)) {
        return (int) $identifier === (int) $document['id'];
    }

    $readableId = trim((string) ($document['readable_id'] ?? ''));

    return $readableId !== '' && strcasecmp($readableId, $identifier) === 0;
}

function document_review_token_valid(array $document, ?string $token): bool {
    $expected = trim((string) ($document['staff_review_token'] ?? ''));
    $token = trim((string) $token);

    return $expected !== '' && hash_equals($expected, $token);
}

function search_documents(?string $query = null): array {
    $query = trim((string) $query);
    if ($query === '') {
        return db()->query('
            SELECT d.*, s.name AS creator_name
            FROM documents d
            JOIN staff s ON s.id = d.created_by
            ORDER BY d.created_at DESC
        ')->fetchAll();
    }

    $stmt = db()->prepare('
        SELECT d.*, s.name AS creator_name
        FROM documents d
        JOIN staff s ON s.id = d.created_by
        WHERE lower(d.title) LIKE lower(?) ESCAPE ?
        ORDER BY d.created_at DESC
    ');
    $stmt->execute(['%' . escape_like($query) . '%', '\\']);

    return $stmt->fetchAll();
}

function escape_like(string $value): string {
    return str_replace(
        ['\\', '%', '_'],
        ['\\\\', '\\%', '\\_'],
        $value
    );
}

function update_document_schedule(int $documentId, string $publishedAt): void {
    $stmt = db()->prepare('UPDATE documents SET published_at = ? WHERE id = ?');
    $stmt->execute([$publishedAt, $documentId]);
}

function create_share(int $documentId, string $email): string {
    $token = random_token();
    $stmt = db()->prepare('
        INSERT INTO shares (document_id, token, recipient_email)
        VALUES (?, ?, ?)
    ');
    $stmt->execute([$documentId, $token, $email]);
    $shareId = (int) db()->lastInsertId();
    audit_log('create', 'share', $shareId, [
        'document_id' => $documentId,
        'recipient_email' => $email,
    ]);

    return $token;
}
