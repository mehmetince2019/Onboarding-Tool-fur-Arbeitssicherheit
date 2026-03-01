<?php
require 'admin_common.php';
require_admin();
require 'config.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

$msg = '';
$err = '';

$pdo->exec("CREATE TABLE IF NOT EXISTS settings (skey VARCHAR(64) PRIMARY KEY, svalue VARCHAR(255) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$title = 'Arbeitssicherheit Tests';
$threshold = 70;
$defaultLang = 'de';
$stmt = $pdo->prepare("SELECT skey, svalue FROM settings WHERE skey IN ('test_title','passing_threshold','default_lang')");
$stmt->execute();
foreach ($stmt->fetchAll() as $r) {
    if ($r['skey'] === 'test_title') $title = $r['svalue'];
    if ($r['skey'] === 'passing_threshold') $threshold = max(0, min(100, (int)$r['svalue']));
    if ($r['skey'] === 'default_lang') $defaultLang = in_array($r['svalue'], ['de','en','tr'], true) ? $r['svalue'] : 'de';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $token = $_POST['csrf_token'] ?? '';
    $newTitle = trim($_POST['test_title'] ?? '');
    $newTh = isset($_POST['passing_threshold']) ? (int)$_POST['passing_threshold'] : 70;
    $newDL = $_POST['default_lang'] ?? 'de';
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $err = 'Ungültiger Vorgang.';
    } elseif ($newTitle === '') {
        $err = 'Titel darf nicht leer sein.';
    } elseif ($newTh < 0 || $newTh > 100) {
        $err = 'Ungültige Bestehensgrenze.';
    } elseif (!in_array($newDL, ['de','en','tr'], true)) {
        $err = 'Ungültige Sprache.';
    } else {
        $ins = $pdo->prepare("INSERT INTO settings (skey, svalue) VALUES ('test_title', ?) ON DUPLICATE KEY UPDATE svalue=VALUES(svalue)");
        $ins->execute([$newTitle]);
        $ins2 = $pdo->prepare("INSERT INTO settings (skey, svalue) VALUES ('passing_threshold', ?) ON DUPLICATE KEY UPDATE svalue=VALUES(svalue)");
        $ins2->execute([strval($newTh)]);
        $ins3 = $pdo->prepare("INSERT INTO settings (skey, svalue) VALUES ('default_lang', ?) ON DUPLICATE KEY UPDATE svalue=VALUES(svalue)");
        $ins3->execute([$newDL]);
        $title = $newTitle;
        $threshold = $newTh;
        $defaultLang = $newDL;
        $_SESSION['lang'] = $newDL;
        $msg = 'Einstellungen gespeichert.';
    }
}

admin_header(__('config_title'));
?>
<h1 class="h4 mb-3"><?= htmlspecialchars(__('config_title')) ?></h1>
<?php if ($msg): ?>
    <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>
<?php if ($err): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
<?php endif; ?>
<div class="card p-4 shadow-sm bg-white" style="max-width:700px">
    <form method="post">
        <input type="hidden" name="save_settings" value="1">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <div class="mb-3">
            <label class="form-label"><?= htmlspecialchars(__('test_title_label')) ?></label>
            <input type="text" name="test_title" class="form-control" required value="<?= htmlspecialchars($title) ?>">
        </div>
        <div class="mb-3" style="max-width:200px">
            <label class="form-label"><?= htmlspecialchars(__('passing_threshold_label')) ?> (%)</label>
            <input type="number" name="passing_threshold" min="0" max="100" class="form-control" required value="<?= (int)$threshold ?>">
        </div>
        <div class="mb-3" style="max-width:220px">
            <label class="form-label"><?= htmlspecialchars(__('default_language')) ?></label>
            <select class="form-select" name="default_lang">
                <option value="de" <?= $defaultLang==='de'?'selected':'' ?>>Deutsch</option>
                <option value="en" <?= $defaultLang==='en'?'selected':'' ?>>English</option>
                <option value="tr" <?= $defaultLang==='tr'?'selected':'' ?>>Türkçe</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><?= htmlspecialchars(__('save')) ?></button>
        <a href="admin_dashboard.php" class="btn btn-outline-secondary ms-2"><?= htmlspecialchars(__('back')) ?></a>
    </form>
</div>

<div class="row g-3 mt-3">
    <div class="col-md-6">
        <div class="card p-3 shadow-sm">
            <h2 class="h6 mb-3"><?= htmlspecialchars(__('logo_title')) ?></h2>
            <a href="admin_logo.php" class="btn btn-outline-secondary"><?= htmlspecialchars(__('logo_title')) ?></a>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card p-3 shadow-sm">
            <h2 class="h6 mb-3"><?= htmlspecialchars(__('nav_password')) ?></h2>
            <a href="admin_password.php" class="btn btn-outline-secondary"><?= htmlspecialchars(__('nav_password')) ?></a>
        </div>
    </div>
</div>
<?php admin_footer(); ?>
