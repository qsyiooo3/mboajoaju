<?php
// auth/middleware.php - Verificación de autenticación y roles
require_once __DIR__ . '/../config/config.php';

/**
 * Verifica que el usuario esté autenticado
 */
function requireAuth() {
    if (!isset($_SESSION['usuario_id'])) {
        // Guardar la URL a la que intentaba acceder
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . URL_BASE . 'auth/login.php');
        exit;
    }
    
    // Verificar tiempo de sesión
    if (isset($_SESSION['ultimo_acceso']) && (time() - $_SESSION['ultimo_acceso'] > SESSION_TIMEOUT)) {
        session_destroy();
        header('Location: ' . URL_BASE . 'auth/login.php?expired=1');
        exit;
    }
    
    $_SESSION['ultimo_acceso'] = time();
}

/**
 * Verifica que el usuario tenga un rol específico
 */
function requireRole($role) {
    requireAuth();
    if ($_SESSION['rol'] !== $role && $_SESSION['rol'] !== 'admin') {
        header('Location: ' . URL_BASE . 'error/403.php');
        exit;
    }
}

/**
 * Verifica que el usuario tenga al menos uno de los roles especificados
 */
function requireAnyRole($roles = []) {
    requireAuth();
    if (!in_array($_SESSION['rol'], $roles) && $_SESSION['rol'] !== 'admin') {
        header('Location: ' . URL_BASE . 'error/403.php');
        exit;
    }
}

/**
 * Obtiene los datos del usuario actual
 */
function getCurrentUser($pdo) {
    if (!isset($_SESSION['usuario_id'])) {
        return null;
    }
    
    $stmt = $pdo->prepare("
        SELECT u.*, 
               a.id as alumno_id, a.curso, a.seccion, a.cedula as alumno_cedula,
               p.id as profesor_id, p.especialidad, p.cedula as profesor_cedula
        FROM usuarios u
        LEFT JOIN alumnos a ON u.id = a.id_usuario
        LEFT JOIN profesores p ON u.id = p.id_usuario
        WHERE u.id = ? AND u.activo = 1
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    return $stmt->fetch();
}

/**
 * Verifica si el usuario tiene permiso en un equipo/canal
 */
function hasTeamPermission($pdo, $userId, $teamId, $requiredRole = 'profesor') {
    $stmt = $pdo->prepare("
        SELECT rol FROM equipo_miembros 
        WHERE usuario_id = ? AND equipo_id = ?
    ");
    $stmt->execute([$userId, $teamId]);
    $member = $stmt->fetch();
    
    if (!$member) return false;
    
    $roleHierarchy = ['alumno' => 0, 'profesor' => 1, 'admin' => 2];
    return $roleHierarchy[$member['rol']] >= $roleHierarchy[$requiredRole];
}

/**
 * Verifica si el usuario es administrador de un equipo
 */
function isTeamAdmin($pdo, $userId, $teamId) {
    $stmt = $pdo->prepare("
        SELECT rol FROM equipo_miembros 
        WHERE usuario_id = ? AND equipo_id = ? AND rol = 'admin'
    ");
    $stmt->execute([$userId, $teamId]);
    return $stmt->fetch() !== false;
}