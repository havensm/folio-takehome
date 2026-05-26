<?php

require __DIR__ . '/../lib/bootstrap.php';
require __DIR__ . '/../lib/layout.php';

$staff = current_staff();

render_header('About', $staff);
?>

<h1 class="page-title">About Folio</h1>
<p class="page-subtitle">Folio helps staff prepare documents and share controlled recipient links.</p>

<section class="card">
    <h2 class="card-title">What the app does</h2>
    <p class="body-copy">
        Folio is a small document-sharing workflow for internal staff. Staff can create document content,
        schedule when it becomes available, review it before sending, and generate recipient-specific share
        links.
    </p>
    <p class="body-copy">
        Recipients open a private tokenized link. If the document is scheduled for the future, Folio shows a
        not-yet-available message instead of exposing the document body.
    </p>
</section>

<section class="card">
    <h2 class="card-title">Core capabilities</h2>
    <ul class="feature-list">
        <li><strong>Scheduled publishing:</strong> prepare documents in advance and control when recipients can view them.</li>
        <li><strong>Readable IDs:</strong> use short document IDs that staff can read, type, and include in URLs.</li>
        <li><strong>Share by title:</strong> find a document by title before creating a share link.</li>
        <li><strong>Staff review:</strong> open a document review page from the admin table before sharing.</li>
        <li><strong>Audit trail:</strong> log document creation, schedule changes, and share creation.</li>
    </ul>
</section>

<section class="card">
    <h2 class="card-title">Security model</h2>
    <p class="body-copy">
        Readable document IDs are for staff convenience and URL context. Recipient access still requires the
        private share token, so readable IDs do not replace the existing token-based access control.
    </p>
</section>

<?php render_footer(); ?>
