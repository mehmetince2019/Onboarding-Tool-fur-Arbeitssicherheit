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
$pdo->exec("CREATE TABLE IF NOT EXISTS branding (
    id TINYINT PRIMARY KEY,
    logo_blob LONGBLOB NULL,
    logo_mime VARCHAR(64) NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $err = 'Ungültiger Vorgang.';
    } elseif (isset($_POST['clear_logo'])) {
        $pdo->prepare("DELETE FROM branding WHERE id = 1")->execute();
        $pdo->prepare("DELETE FROM settings WHERE skey = 'logo_url'")->execute();
        $msg = 'Logo entfernt.';
    } elseif (isset($_POST['save_url'])) {
        $url = trim($_POST['logo_url'] ?? '');
        if ($url === '' || !preg_match('~^https?://~i', $url)) {
            $err = 'Geçerli bir URL girin.';
        } else {
            $ins = $pdo->prepare("INSERT INTO settings (skey, svalue) VALUES ('logo_url', ?) ON DUPLICATE KEY UPDATE svalue=VALUES(svalue)");
            $ins->execute([$url]);
            $pdo->prepare("DELETE FROM branding WHERE id = 1")->execute();
            $msg = 'Logo URL kaydedildi.';
        }
    } elseif (isset($_POST['upload_logo']) && isset($_FILES['logo']) && is_uploaded_file($_FILES['logo']['tmp_name'])) {
        $f = $_FILES['logo'];
        if ($f['error'] !== UPLOAD_ERR_OK) {
            $err = 'Yükleme hatası.';
        } elseif ($f['size'] > 2 * 1024 * 1024) {
            $err = 'Maksimum 2MB.';
        } else {
            $mime = mime_content_type($f['tmp_name']) ?: 'application/octet-stream';
            if (!preg_match('~^image/(png|jpeg|jpg|gif|svg\+xml)$~i', $mime)) {
                $err = 'Desteklenmeyen format.';
            } else {
                $blob = file_get_contents($f['tmp_name']);
                $stmt = $pdo->prepare("REPLACE INTO branding (id, logo_blob, logo_mime) VALUES (1, ?, ?)");
                $stmt->execute([$blob, $mime]);
                $pdo->prepare("DELETE FROM settings WHERE skey = 'logo_url'")->execute();
                $msg = 'Logo yüklendi.';
            }
        }
    }
}

$currentUrl = $pdo->query("SELECT svalue FROM settings WHERE skey = 'logo_url' LIMIT 1")->fetchColumn() ?: '';
$hasBlob = (bool)$pdo->query("SELECT 1 FROM branding WHERE id = 1 AND logo_blob IS NOT NULL LIMIT 1")->fetchColumn();

admin_header(__('logo_title'));
?>
<h1 class="h4 mb-3"><?= htmlspecialchars(__('logo_title')) ?></h1>
<?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card p-3 shadow-sm">
            <h2 class="h6 mb-3"><?= htmlspecialchars(__('upload')) ?></h2>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <div class="mb-3">
                    <input type="file" name="logo" class="form-control" accept="image/*" required>
                    <div class="form-text"><?= htmlspecialchars(__('max_2mb_hint')) ?></div>
                </div>
                <button class="btn btn-primary" name="upload_logo" value="1"><?= htmlspecialchars(__('upload_logo')) ?></button>
            </form>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card p-3 shadow-sm">
            <h2 class="h6 mb-3"><?= htmlspecialchars(__('url')) ?></h2>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <div class="mb-3">
                    <input type="url" name="logo_url" class="form-control" placeholder="https://example.com/logo.png" value="<?= htmlspecialchars($currentUrl) ?>">
                </div>
                <button class="btn btn-outline-primary" name="save_url" value="1"><?= htmlspecialchars(__('save_url')) ?></button>
            </form>
        </div>
    </div>
</div>

<div class="card p-3 shadow-sm mt-4">
    <h2 class="h6 mb-3"><?= htmlspecialchars(__('preview')) ?></h2>
    <div class="d-flex align-items-center gap-3">
        <img src="logo.php?ts=<?= time() ?>" alt="logo" style="height:48px" class="border p-1 bg-white">
        <form method="post" onsubmit="return confirm(<?= json_encode(__('remove_logo_confirm')) ?>);">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <button class="btn btn-outline-danger" name="clear_logo" value="1" <?= ($hasBlob || $currentUrl) ? '' : 'disabled' ?>><?= htmlspecialchars(__('remove_logo')) ?></button>
        </form>
    </div>
</div>

<?php admin_footer(); ?>
