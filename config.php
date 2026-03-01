<?php
$host = 'localhost';
$db   = 'arbeitsschutz';
$user = 'root';
$pass = ''; // XAMPP'ta genelde boş; gerekirse kendi MySQL şifreni yaz

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('Veritabanı bağlantı hatası: ' . $e->getMessage());
}
