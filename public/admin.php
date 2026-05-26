<?php

require __DIR__ . '/../lib/bootstrap.php';
require __DIR__ . '/../lib/documents.php';
require __DIR__ . '/../lib/layout.php';

$staff = current_staff();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create_document';

    try {
        if ($action === 'update_schedule') {
            $doc = document_by_identifier((string) ($_POST['doc'] ?? ''));
            if (!$doc) {
                $error = 'Document not found.';
            } else {
                $publishedAt = parse_publish_input($_POST['published_at'] ?? '');
                update_document_schedule((int) $doc['id'], $publishedAt);
                audit_log('schedule', 'document', (int) $doc['id'], [
                    'from' => $doc['published_at'],
                    'to' => $publishedAt,
                ]);

                header('Location: /admin.php?scheduled=' . urlencode($doc['readable_id']));
                exit;
            }
        } else {
            $title = trim($_POST['title'] ?? '');
            $body = trim($_POST['body'] ?? '');
            $publishInput = trim($_POST['published_at'] ?? '');

            if ($title === '' || $body === '') {
                $error = 'Title and body are required.';
            } else {
                $publishedAt = parse_publish_input($publishInput);
                $docId = create_document($title, $body, (int) $staff['id'], $publishedAt);
                $doc = document_by_identifier((string) $docId);

                audit_log('create', 'document', $docId, [
                    'title' => $title,
                    'readable_id' => $doc['readable_id'],
                    'published_at' => $publishedAt,
                ]);

                if ($publishInput !== '') {
                    audit_log('schedule', 'document', $docId, [
                        'from' => null,
                        'to' => $publishedAt,
                    ]);
                }

                header('Location: /admin.php?created=' . urlencode($doc['readable_id']));
                exit;
            }
        }
    } catch (InvalidArgumentException $e) {
        $error = $e->getMessage();
    }
}

$search = trim($_GET['q'] ?? '');
$docs = search_documents($search);

render_header('Admin', $staff);
?>

<h1 class="page-title">Admin</h1>
<p class="page-subtitle">Create documents and generate share links for recipients.</p>

<?php if (!empty($_GET['created'])): ?>
    <div class="banner banner-success">Document <?= h((string) $_GET['created']) ?> created.</div>
<?php endif ?>

<?php if (!empty($_GET['scheduled'])): ?>
    <div class="banner banner-success">Schedule updated for <?= h((string) $_GET['scheduled']) ?>.</div>
<?php endif ?>

<?php if ($error): ?>
    <div class="banner banner-error"><?= h($error) ?></div>
<?php endif ?>

<section class="card">
    <h2 class="card-title">New document</h2>
    <form method="post">
        <input type="hidden" name="action" value="create_document">
        <div class="form-field">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" required>
        </div>
        <div class="form-field">
            <label for="body">Body</label>
            <textarea id="body" name="body" required></textarea>
        </div>
        <div class="form-field">
            <label for="published_at">Available to recipients</label>
            <input type="datetime-local" id="published_at" name="published_at">
            <p class="help-text">Leave blank to publish immediately. Times use <?= h(date_default_timezone_get()) ?>.</p>
        </div>
        <button type="submit" class="btn">Create document</button>
    </form>
</section>

<section class="card">
    <h2 class="card-title">Documents</h2>
    <form method="get" class="search-form">
        <div class="form-field search-field">
            <label for="q">Find by title</label>
            <input type="text" id="q" name="q" value="<?= h($search) ?>" placeholder="Search document titles">
        </div>
        <button type="submit" class="btn">Search</button>
        <?php if ($search !== ''): ?>
            <a href="/admin.php" class="btn-link">Clear</a>
        <?php endif ?>
    </form>

    <?php if (empty($docs)): ?>
        <p class="empty"><?= $search === '' ? 'No documents yet.' : 'No matching documents.' ?></p>
    <?php else: ?>
        <div class="table-wrap">
        <table class="data documents-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Available</th>
                    <th>Creator</th>
                    <th>Created</th>
                    <th class="actions-heading">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($docs as $d): ?>
                    <tr>
                        <td class="id"><?= h($d['readable_id'] ?? ('#' . $d['id'])) ?></td>
                        <td><?= h($d['title']) ?></td>
                        <td>
                            <form method="post" class="schedule-form">
                                <input type="hidden" name="action" value="update_schedule">
                                <input type="hidden" name="doc" value="<?= h($d['readable_id'] ?? (string) $d['id']) ?>">
                                <input
                                    type="datetime-local"
                                    name="published_at"
                                    value="<?= h(format_datetime_local($d['published_at'] ?? null)) ?>"
                                    aria-label="Available time for <?= h($d['title']) ?>"
                                >
                                <button type="submit" class="btn-link">Save</button>
                            </form>
                            <?php if (!document_is_published($d)): ?>
                                <span class="status status-waiting">Scheduled</span>
                            <?php else: ?>
                                <span class="status status-live">Live</span>
                            <?php endif ?>
                        </td>
                        <td><?= h($d['creator_name']) ?></td>
                        <td><?= h($d['created_at']) ?></td>
                        <td class="actions-cell">
                            <a href="/share.php?doc=<?= urlencode($d['readable_id'] ?? (string) $d['id']) ?>" class="btn-link action-link">Create share</a>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
        </div>
    <?php endif ?>
</section>

<?php render_footer(); ?>
