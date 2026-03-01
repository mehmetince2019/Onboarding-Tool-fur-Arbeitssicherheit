<?php
require 'config.php';
require_once __DIR__ . '/i18n.php';
$title = 'Arbeitssicherheit Tests';
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (skey VARCHAR(64) PRIMARY KEY, svalue VARCHAR(255) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $st = $pdo->prepare("SELECT svalue FROM settings WHERE skey = 'test_title' LIMIT 1");
    $st->execute();
    $r = $st->fetch();
    if ($r && isset($r['svalue']) && $r['svalue'] !== '') $title = $r['svalue'];
} catch (Exception $e) {}
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
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <img src="logo.php" alt="logo" style="height:48px">
        <div><?= render_lang_switcher() ?></div>
    </div>
    <h1 class="mb-4"><?= htmlspecialchars($title) ?></h1>

    <div class="card p-4 shadow-sm bg-white mb-4">
        <h2 class="h5 mb-3"><?= htmlspecialchars(__('index_info_title')) ?></h2>
        <p class="mb-2"><?= htmlspecialchars(__('index_info_p')) ?></p>
        <ul class="mb-0">
            <li><strong><?= htmlspecialchars(__('index_info_1')) ?></strong></li>
            <li><strong><?= htmlspecialchars(__('index_info_2')) ?></strong></li>
            <li><strong><?= htmlspecialchars(__('index_info_3')) ?></strong></li>
        </ul>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="test.php" class="btn btn-success btn-lg"><?= htmlspecialchars(__('index_btn_start')) ?></a>
        <a href="admin_login.php" class="btn btn-outline-secondary"><?= htmlspecialchars(__('index_btn_admin')) ?></a>
    </div>
</div>
</body>
</html>
