<?php
// ============================================================
// CONFIG / DATABASE.EXAMPLE.PHP — Mboa' Joaju v2.0
// Copia este archivo a database.php y ajusta los valores
// ============================================================

// ── Conexión PDO ───────────────────────────────────────
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    $pdo->exec("SET NAMES '" . DB_CHARSET . "'");

} catch(PDOException $e) {
    error_log("Error de conexión PDO a la base de datos: " . $e->getMessage());
    http_response_code(500);
    die("Error de conexión a la base de datos. Verifica que MySQL esté activo.");
}

// ── Conexión MySQLi ────────────────────────────────────
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    error_log("Error de conexión MySQLi a la base de datos: " . $conn->connect_error);
    http_response_code(500);
    die("Error de conexión a la base de datos. Verifica que MySQL esté activo.");
}
$conn->set_charset(DB_CHARSET);