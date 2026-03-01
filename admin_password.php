<?php
require 'admin_common.php';
require_admin();
require 'config.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $token = $_POST['csrf_token'] ?? '';
    $current = $_POST['current_password'] ?? '';
    $new1 = $_POST['new_password'] ?? '';
    $new2 = $_POST['new_password2'] ?? '';
    $username = $_SESSION['admin_user'] ?? 'admin';
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $err = 'Ungültiger Vorgang.';
    } elseif ($new1 === '' || strlen($new1) < 6) {
        $err = 'Neues Passwort muss mindestens 6 Zeichen lang sein.';
    } elseif ($new1 !== $new2) {
        $err = 'Neue Passwörter stimmen nicht überein.';
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS admins (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(60) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        $stmt = $pdo->prepare("SELECT id, password_hash FROM admins WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $row = $stmt->fetch();
        if (!$row || !password_verify($current, $row['password_hash'])) {
            $err = 'Aktuelles Passwort ist falsch.';
        } else {
            $hash = password_hash($new1, PASSWORD_BCRYPT);
            $upd = $pdo->prepare("UPDATE admins SET password_hash = ? WHERE id = ?");
            $upd->execute([$hash, (int)$row['id']]);
            $msg = 'Passwort wurde aktualisiert.';
        }
    }
}

admin_header('Passwort ändern');
?>
<h1 class="h4 mb-3">Passwort ändern</h1>
<?php if ($msg): ?>
    <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>
<?php if ($err): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
<?php endif; ?>
<div class="card p-4 shadow-sm bg-white" style="max-width:520px">
    <form method="post">
        <input type="hidden" name="change_password" value="1">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <div class="mb-3">
            <label class="form-label">Aktuelles Passwort</label>
            <input type="password" name="current_password" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Neues Passwort</label>
            <input type="password" name="new_password" class="form-control" required minlength="6">
        </div>
        <div class="mb-3">
            <label class="form-label">Neues Passwort (Wiederholung)</label>
            <input type="password" name="new_password2" class="form-control" required minlength="6">
        </div>
        <button type="submit" class="btn btn-primary">Speichern</button>
        <a href="admin_dashboard.php" class="btn btn-outline-secondary ms-2">Zurück</a>
    </form>
    </div>
<?php admin_footer(); ?>
