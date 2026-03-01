<?php
session_start();
require_once __DIR__ . '/i18n.php';

function require_admin(): void {
    if (empty($_SESSION['is_admin'])) {
        header('Location: admin_login.php');
        exit;
    }
}

function admin_header(string $title): void {
    $brand = 'Arbeitssicherheit';
    if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
        try {
            $GLOBALS['pdo']->exec("CREATE TABLE IF NOT EXISTS settings (skey VARCHAR(64) PRIMARY KEY, svalue VARCHAR(255) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            $stmt = $GLOBALS['pdo']->prepare("SELECT svalue FROM settings WHERE skey = 'test_title' LIMIT 1");
            $stmt->execute();
            $row = $stmt->fetch();
            if ($row && isset($row['svalue']) && $row['svalue'] !== '') {
                $brand = $row['svalue'];
            }
        } catch (Exception $e) {}
    }
    ?>
    <!doctype html>
    <html lang="de">
    <head>
        <meta charset="utf-8">
        <title><?= htmlspecialchars($title) ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="admin_dashboard.php">
                <img src="logo.php" alt="logo" style="height:28px">
                <span><?= htmlspecialchars($brand) ?> <?= htmlspecialchars(__('admin_suffix')) ?></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="nav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="admin_dashboard.php"><?= htmlspecialchars(__('nav_overview')) ?></a></li>
                    <li class="nav-item"><a class="nav-link" href="admin_questions.php"><?= htmlspecialchars(__('nav_questions')) ?></a></li>
                    <li class="nav-item"><a class="nav-link" href="admin_results.php"><?= htmlspecialchars(__('nav_results')) ?></a></li>
                    <li class="nav-item"><a class="nav-link" href="admin_todos.php"><?= htmlspecialchars(__('nav_todos')) ?></a></li>
                </ul>
                <div class="d-flex gap-2">
                    <a class="btn btn-outline-light btn-sm" href="admin_settings.php"><?= htmlspecialchars(__('nav_config')) ?></a>
                    <a class="btn btn-outline-light btn-sm" href="admin_logout.php"><?= htmlspecialchars(__('nav_logout')) ?></a>
                </div>
            </div>
        </div>
    </nav>
    <div class="container py-4">
    <?php
}

function admin_footer(): void {
    ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
}

