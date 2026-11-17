<?php
require_once 'config.php';

try {
    // Tomar el endpoint definido en config.php o extraerlo del host si estuviera vacío
    $endpoint = defined('DB_ENDPOINT') ? DB_ENDPOINT : '';
    
    // Construir el DSN base con SSL requerido por Neon
    $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";sslmode=require";
    
    // Si tenemos un endpoint, lo agregamos para solucionar el problema de libpq en Windows
    if (!empty($endpoint)) {
        $dsn .= ";options=endpoint%3D" . $endpoint;
    }

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Error de conexión con Neon: " . $e->getMessage());
}
?>