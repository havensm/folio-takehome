<?php

require __DIR__ . '/../lib/bootstrap.php';
require __DIR__ . '/../lib/documents.php';
require __DIR__ . '/../lib/layout.php';

$staff = current_staff();
$doc = document_by_identifier((string) ($_GET['doc'] ?? ''));

if (!$doc) {
    http_response_code(404);
    render_header('Not found', $staff);
    ?>
    <div class="banner banner-error">Document not found.</div>
    <p><a href="/admin.php" class="back-link">← back to admin</a></p>
    <?php
    render_footer();
    exit;
}

if (!document_review_token_valid($doc, $_GET['review_token'] ?? '')) {
    http_response_code(403);
    render_header('Review link required', $staff);
    ?>
    <div class="banner banner-error">A valid staff review link is required to view this document.</div>
    <p><a href="/admin.php" class="back-link">← back to admin</a></p>
    <?php
    render_footer();
    exit;
}

render_header('Review · ' . $doc['title'], $staff);
?>

<a href="/admin.php" class="back-link">← back to admin</a>

<div class="page-actions">
    <div>
        <h1 class="page-title"><?= h($doc['title']) ?></h1>
        <p class="page-subtitle">Review document <?= h($doc['readable_id'] ?? ('#' . $doc['id'])) ?> before sharing it.</p>
    </div>
    <a href="/share.php?doc=<?= urlencode($doc['readable_id'] ?? (string) $doc['id']) ?>" class="btn">Create share</a>
</div>

<section class="card">
    <h2 class="card-title">Publishing</h2>
    <dl class="detail-list">
        <div>
            <dt>Status</dt>
            <dd>
                <?php if (document_is_published($doc)): ?>
                    <span class="status status-live">Live</span>
                <?php else: ?>
                    <span class="status status-waiting">Scheduled</span>
                <?php endif ?>
            </dd>
        </div>
        <div>
            <dt>Available</dt>
            <dd><?= h(format_display_datetime($doc['published_at'] ?? null)) ?></dd>
        </div>
        <div>
            <dt>Created</dt>
            <dd><?= h($doc['created_at']) ?></dd>
        </div>
    </dl>
</section>

<section class="card">
    <h2 class="card-title">Document body</h2>
    <pre class="doc-body"><?= h($doc['body']) ?></pre>
</section>

<?php render_footer(); ?>
