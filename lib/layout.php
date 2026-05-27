<?php

function render_header(string $title, ?array $staff = null): void {
    ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($title) ?> · Folio</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<nav class="nav">
    <div class="nav-inner">
        <a href="/admin.php" class="brand">
            <span class="brand-mark">CP</span>
            <span>
                <span class="brand-name">Folio</span>
                <span class="brand-subtitle">CivicPlus</span>
            </span>
        </a>
        <?php if ($staff): ?>
            <span class="nav-user"><strong><?= h($staff['name']) ?></strong> · <?= h($staff['email']) ?></span>
        <?php endif ?>
    </div>
</nav>
<main class="container">
    <?php
}

function render_footer(): void {
    ?>
</main>
<footer class="site-footer">
    <div class="footer-inner">
        <div>
            <strong>Folio</strong>
            <span>Document sharing for CivicPlus teams.</span>
        </div>
        <nav class="footer-links" aria-label="Footer">
            <a href="/about.php">About</a>
            <a href="/admin.php">Admin</a>
        </nav>
    </div>
</footer>
</body>
</html>
    <?php
}
