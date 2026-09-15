<?php
// ============================================================
// INCLUDES / SECURITY.PHP — Mboa' Joaju v2.0
// Funciones de seguridad centralizadas (Merge)
// ============================================================

// ── Autenticación ─────────────────────────────────────

function estaAutenticado(): bool {
    return isset($_SESSION['id_usuario']) && !empty($_SESSION['id_usuario']);
}

function tieneRol(string $rol): bool {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === $rol;
}

function esAdmin(): bool {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
}

function esProfesor(): bool {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'profesor';
}

function esAlumno(): bool {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'alumno';
}

function rolActual(): ?string {
    return $_SESSION['rol'] ?? null;
}

function esPropioUsuario(int $id_usuario): bool {
    return isset($_SESSION['id_usuario']) && $_SESSION['id_usuario'] === $id_usuario;
}

// ── Redirecciones de seguridad ────────────────────────

function requireAuth(): void {
    if (!estaAutenticado()) {
        header('Location: ' . BASE_URL . 'auth/login.php');
        exit();
    }
}

function requireRole(string $rol): void {
    requireAuth();
    if (!tieneRol($rol) && !esAdmin()) {
        header('Location: ' . BASE_URL . 'error/403.php');
        exit();
    }
}

// ── CSRF ──────────────────────────────────────────────

function validarCSRF(): void {
    if (
        !isset($_POST['csrf_token']) ||
        !isset($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        http_response_code(403);
        die("Error de seguridad. Recargue la pagina e intente nuevamente.");
    }
}

// ── Sanitización ──────────────────────────────────────

function sanitizarInput(string $valor): string {
    return trim(strip_tags($valor));
}

function sanitizarNombreArchivo(string $nombre): string {
    $nombre = preg_replace('/[^a-zA-Z0-9._-]/', '', $nombre);
    $nombre = str_replace(' ', '-', $nombre);
    return $nombre;
}

// ── Validaciones ──────────────────────────────────────

function validarEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validarContrasena(string $password): bool {
    return strlen($password) >= 6;
}

function safeJsonEncode($data) {
    return json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}

function isAjaxRequest() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

// ── Utilidades de seguridad ───────────────────────────

function generarContrasena(int $longitud = 12): string {
    $caracteres = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+';
    $longitud_caracteres = strlen($caracteres);
    $contrasena = '';
    for ($i = 0; $i < $longitud; $i++) {
        $contrasena .= $caracteres[random_int(0, $longitud_caracteres - 1)];
    }
    return $contrasena;
}

function escaparLike(string $valor): string {
    return str_replace(['%', '_'], ['\%', '\_'], $valor);
}

function checkRateLimit($pdo, $userId, $action, $limit = 10, $timeWindow = 60) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM logs_actividad WHERE id_usuario = ? AND accion = ? AND fecha > DATE_SUB(NOW(), INTERVAL ? SECOND)");
    $stmt->execute([$userId, $action, $timeWindow]);
    $count = $stmt->fetchColumn();
    
    if ($count >= $limit) {
        http_response_code(429);
        die(json_encode(['error' => 'Demasiadas solicitudes. Por favor espera un momento.', 'retry_after' => $timeWindow]));
    }
}
?>
