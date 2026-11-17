<?php
require_once 'config.php';

try {
    $endpoint = preg_replace('/-pooler$/', '', DB_ENDPOINT);
    $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";sslmode=require;options=endpoint=" . $endpoint;
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Error de conexión con Neon: " . $e->getMessage());
}
?>