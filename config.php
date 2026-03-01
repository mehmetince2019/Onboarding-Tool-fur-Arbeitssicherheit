<?php
$host = 'localhost';
$db   = 'arbeitsschutz';
$user = 'arbeitsxxxuser'; // iher username !!!!
$pass = 'xxxxxxxxxxxx'; //  Ihr MySQL-Passwort !!!!

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('Veritabanı bağlantı hatası: ' . $e->getMessage());
}


