<?php
require 'config.php';
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
$pdo->exec("CREATE TABLE IF NOT EXISTS branding (
    id TINYINT PRIMARY KEY,
    logo_blob LONGBLOB NULL,
    logo_mime VARCHAR(64) NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
$stmt = $pdo->prepare("SELECT logo_blob, logo_mime FROM branding WHERE id = 1");
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row && !empty($row['logo_blob']) && !empty($row['logo_mime'])) {
    header('Content-Type: ' . $row['logo_mime']);
    echo $row['logo_blob'];
    exit;
}
$pdo->exec("CREATE TABLE IF NOT EXISTS settings (skey VARCHAR(64) PRIMARY KEY, svalue VARCHAR(255) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
$st = $pdo->prepare("SELECT svalue FROM settings WHERE skey = 'logo_url' LIMIT 1");
$st->execute();
$r = $st->fetch();
if ($r && !empty($r['svalue'])) {
    header('Location: ' . $r['svalue'], true, 302);
    exit;
}
header('Content-Type: image/svg+xml; charset=utf-8');
echo '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="48"><rect width="200" height="48" fill="#2c3e50"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="#ecf0f1" font-family="Arial" font-size="16">Logo</text></svg>';
