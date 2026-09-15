<?php
// ============================================================
// ADMIN / USUARIOS_ELIMINAR.PHP — Mboa' Joaju v2.0
// Eliminar usuario
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$token = $_GET['token'] ?? '';

// ── Validar token ──────────────────────────────────────────
if (!hash_equals($_SESSION['csrf_token'], $token)) {
    $_SESSION['error_mensaje'] = 'Token de seguridad inválido.';
    $_SESSION['error_tipo'] = 'danger';
    header('Location: ' . BASE_URL . 'admin/usuarios.php');
    exit();
}

if ($id <= 0) {
    $_SESSION['error_mensaje'] = 'ID de usuario inválido.';
    $_SESSION['error_tipo'] = 'danger';
    header('Location: ' . BASE_URL . 'admin/usuarios.php');
    exit();
}

// ── Verificar que no sea admin ─────────────────────────────
$stmt = $conn->prepare("SELECT rol, nombre_completo FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();
$stmt->close();

if (!$usuario) {
    $_SESSION['error_mensaje'] = 'Usuario no encontrado.';
    $_SESSION['error_tipo'] = 'danger';
    header('Location: ' . BASE_URL . 'admin/usuarios.php');
    exit();
}

if ($usuario['rol'] === 'admin') {
    $_SESSION['error_mensaje'] = 'No se puede eliminar al administrador principal.';
    $_SESSION['error_tipo'] = 'danger';
    header('Location: ' . BASE_URL . 'admin/usuarios.php');
    exit();
}

// ── Eliminar usuario (cascade elimina perfil) ─────────────
if ($conn->query("DELETE FROM usuarios WHERE id = $id")) {
    registrarLog($_SESSION['id_usuario'], 'eliminar_usuario', "Eliminó usuario: " . $usuario['nombre_completo']);
    $_SESSION['error_mensaje'] = '✅ Usuario eliminado correctamente.';
    $_SESSION['error_tipo'] = 'success';
} else {
    $_SESSION['error_mensaje'] = 'Error al eliminar el usuario.';
    $_SESSION['error_tipo'] = 'danger';
    error_log("Error eliminar usuario: " . $conn->error);
}

header('Location: ' . BASE_URL . 'admin/usuarios.php');
exit();
?>