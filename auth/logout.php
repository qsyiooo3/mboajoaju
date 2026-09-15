<?php
// auth/logout.php - Cierre de sesión
require_once __DIR__ . '/../config/config.php';

// Registrar actividad
if (isset($_SESSION['usuario_id'])) {
    logActivity($pdo, $_SESSION['usuario_id'], 'logout', 'Cierre de sesión');
}

// Destruir sesión
$_SESSION = [];
session_destroy();

// Eliminar cookie de sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Redirigir al login
header('Location: ' . URL_BASE . 'auth/login.php');
exit;
?>