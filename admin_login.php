<?php
session_start();
require 'config.php';
require_once __DIR__ . '/i18n.php';

const ADMIN_USER = 'admin';
const ADMIN_PASS = 'admin123';

$pdo->exec("
    CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(60) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT id, username, password_hash FROM admins WHERE username = ? LIMIT 1");
    $stmt->execute([$user]);
    $row = $stmt->fetch();

    if ($row && password_verify($pass, $row['password_hash'])) {
        $_SESSION['is_admin'] = true;
        $_SESSION['admin_user'] = $row['username'];
        header('Location: admin_dashboard.php');
        exit;
    }

    if ($user === ADMIN_USER && $pass === ADMIN_PASS) {
        if (!$row) {
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $ins = $pdo->prepare("INSERT IGNORE INTO admins (username, password_hash) VALUES (?, ?)");
            $ins->execute([$user, $hash]);
        }
        $_SESSION['is_admin'] = true;
        $_SESSION['admin_user'] = $user;
        header('Location: admin_dashboard.php');
        exit;
    }

    $error = 'Benutzername oder Passwort ist falsch.';
}
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars(__('login_title')) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="d-flex justify-content-end mb-2"><?= render_lang_switcher() ?></div>
    <h1 class="mb-4"><?= htmlspecialchars(__('index_title')) ?> – <?= htmlspecialchars(__('login_title')) ?></h1>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars(__('login_error')) ?></div>
    <?php endif; ?>

    <form method="post" class="card p-4 shadow-sm bg-white" style="max-width: 400px;">
        <div class="mb-3">
            <label class="form-label"><?= htmlspecialchars(__('username')) ?></label>
            <input type="text" name="username" class="form-control" required autofocus>
        </div>
        <div class="mb-3">
            <label class="form-label"><?= htmlspecialchars(__('password')) ?></label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100"><?= htmlspecialchars(__('login_btn')) ?></button>
    </form>
</div>
</body>
</html>
