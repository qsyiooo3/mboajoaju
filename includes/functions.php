<?php
// ============================================================
// INCLUDES / FUNCTIONS.PHP — Mboa' Joaju v2.0
// Funciones auxiliares reutilizables (Merge)
// ============================================================

// ── Sanitización y escape ──────────────────────────────

function h(string $texto): string {
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}

function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function escapeOutput($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// ── Texto ──────────────────────────────────────────────

function truncar(string $texto, int $max = 100): string {
    if (strlen($texto) <= $max) return $texto;
    return substr($texto, 0, $max) . '…';
}

function truncateText($text, $limit = 150, $ending = '...') {
    if (strlen($text) <= $limit) return $text;
    return substr($text, 0, $limit) . $ending;
}

function resaltar($texto, $busqueda) {
    if (empty($busqueda)) return $texto;
    return preg_replace('/(' . preg_quote($busqueda, '/') . ')/i', '<mark>$1</mark>', $texto);
}

function generateSlug($text) {
    $text = preg_replace('/[^a-zA-Z0-9]/', '-', strtolower($text));
    $text = preg_replace('/-+/', '-', $text);
    return trim($text, '-');
}

// ── Fechas ─────────────────────────────────────────────

function fecha(string $fecha, string $formato = 'd/m/Y H:i'): string {
    $ts = is_numeric($fecha) ? $fecha : strtotime($fecha);
    return date($formato, $ts);
}

function formatDate($date, $format = 'd/m/Y H:i') {
    if (!$date) return '-';
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    return date($format, $timestamp);
}

function formatDateShort($date) {
    return formatDate($date, 'd/m/Y');
}

function fechaInput(string $fecha): string {
    return date('Y-m-d\TH:i', strtotime($fecha));
}

function timeAgo($date) {
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    $diff = time() - $timestamp;
    
    if ($diff < 60) return 'Hace ' . $diff . ' segundos';
    if ($diff < 3600) return 'Hace ' . floor($diff/60) . ' minutos';
    if ($diff < 86400) return 'Hace ' . floor($diff/3600) . ' horas';
    if ($diff < 604800) return 'Hace ' . floor($diff/86400) . ' días';
    if ($diff < 2592000) return 'Hace ' . floor($diff/604800) . ' semanas';
    if ($diff < 31536000) return 'Hace ' . floor($diff/2592000) . ' meses';
    return 'Hace ' . floor($diff/31536000) . ' años';
}

// ── Badges y nombres ──────────────────────────────────

function nombreRol(string $rol): string {
    $roles = [
        'admin' => 'Administrador',
        'profesor' => 'Profesor',
        'alumno' => 'Alumno'
    ];
    return $roles[$rol] ?? $rol;
}

function nombreEstado(string $estado): string {
    $estados = [
        'activo' => 'Activo', 'inactivo' => 'Inactivo', 'suspendido' => 'Suspendido',
        'egresado' => 'Egresado', 'disponible' => 'Disponible', 'en_uso' => 'En uso',
        'mantenimiento' => 'Mantenimiento', 'baja' => 'Baja'
    ];
    return $estados[$estado] ?? $estado;
}

function badgeEstado(string $estado): string {
    $clases = [
        'activo' => 'badge-success', 'inactivo' => 'badge-danger',
        'suspendido' => 'badge-warning', 'egresado' => 'badge-info',
        'disponible' => 'badge-success', 'en_uso' => 'badge-warning',
        'mantenimiento' => 'badge-danger', 'baja' => 'badge-dark'
    ];
    return $clases[$estado] ?? 'badge-secondary';
}

function badgeRol(string $rol): string {
    $clases = [
        'admin' => 'badge-admin', 'profesor' => 'badge-profesor', 'alumno' => 'badge-alumno'
    ];
    return $clases[$rol] ?? 'badge-secondary';
}

// ── Archivos ──────────────────────────────────────────

function getFileIcon($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $icons = [
        'pdf' => '📄', 'doc' => '📘', 'docx' => '📘',
        'xls' => '📊', 'xlsx' => '📊', 'ppt' => '📽️', 'pptx' => '📽️',
        'jpg' => '🖼️', 'jpeg' => '🖼️', 'png' => '🖼️', 'gif' => '🖼️',
        'webp' => '🖼️', 'svg' => '🖼️', 'bmp' => '🖼️', 'ico' => '🖼️',
        'zip' => '📦', 'rar' => '📦', '7z' => '📦', 'tar' => '📦', 'gz' => '📦',
        'txt' => '📝', 'rtf' => '📝', 'odt' => '📘', 'ods' => '📊', 'odp' => '📽️',
        'mp4' => '🎬', 'mp3' => '🎵', 'wav' => '🎵', 'avi' => '🎬', 'mov' => '🎬',
        'csv' => '📊', 'json' => '📝', 'xml' => '📝'
    ];
    return $icons[$ext] ?? '📎';
}

function getFileType($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $types = [
        'pdf' => 'PDF', 'doc' => 'Word', 'docx' => 'Word',
        'xls' => 'Excel', 'xlsx' => 'Excel', 'ppt' => 'PowerPoint', 'pptx' => 'PowerPoint',
        'jpg' => 'Imagen', 'jpeg' => 'Imagen', 'png' => 'Imagen', 'gif' => 'Imagen',
        'zip' => 'Comprimido', 'rar' => 'Comprimido',
        'txt' => 'Texto', 'mp4' => 'Video', 'mp3' => 'Audio', 'wav' => 'Audio'
    ];
    return $types[$ext] ?? 'Archivo';
}

function getFileSize($bytes) {
    return formatearBytes($bytes);
}

function formatearBytes($bytes, $precision = 2) {
    $unidades = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    $b = (int)$bytes;
    while ($b >= 1024 && $i < count($unidades) - 1) {
        $b /= 1024;
        $i++;
    }
    return round($b, $precision) . ' ' . $unidades[$i];
}

function obtenerExtension(string $nombre): string {
    return strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
}

// ── Select options ─────────────────────────────────────

function opcionesSelect(array $items, $selected = null, string $valueField = 'id', string $textField = 'nombre'): string {
    $html = '';
    foreach ($items as $item) {
        $value = is_array($item) ? $item[$valueField] : $item->$valueField;
        $text = is_array($item) ? $item[$textField] : $item->$textField;
        $sel = ($value == $selected) ? ' selected' : '';
        $html .= "<option value=\"" . h($value) . "\"{$sel}>" . h($text) . "</option>";
    }
    return $html;
}

// ── Alertas ───────────────────────────────────────────

function mostrarAlerta(string $mensaje, string $tipo = 'info') {
    $clases = [
        'success' => 'alerta-success', 'danger' => 'alerta-danger',
        'error' => 'alerta-danger', 'warning' => 'alerta-warning', 'info' => 'alerta-info'
    ];
    $clase = $clases[$tipo] ?? 'alerta-info';
    return '<div class="alerta ' . $clase . '">' . h($mensaje) . '</div>';
}

// ── Seguridad ─────────────────────────────────────────

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function getCSRFToken() {
    return generateCSRFToken();
}

function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        error_log("Intento de CSRF detectado");
        return false;
    }
    return true;
}

function regenerateCSRFToken() {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validateCI($ci) {
    return preg_match('/^[0-9]{6,8}$/', $ci);
}

function generateRandomPassword($length = 12) {
    return generarContrasena($length);
}

// ── Base de datos (PDO) ───────────────────────────────

/**
 * Registra actividad en el log.
 * ✅ CORREGIDO: Si $userId es null, usa el ID del admin (1) para
 * cumplir con la restricción NOT NULL + FK de la tabla logs_actividad.
 */
function logActivity($pdo, $userId, $action, $detail = null) {
    try {
        // ── CORRECCIÓN: Sustituir null por admin (id=1) ──
        if ($userId === null || (int)$userId <= 0) {
            $userId = 1; // ID del administrador/sistema
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $stmt = $pdo->prepare("INSERT INTO logs_actividad (id_usuario, accion, detalle, ip, user_agent) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$userId, $action, $detail, $ip, $userAgent]);
    } catch (PDOException $e) {
        error_log("Error al registrar actividad: " . $e->getMessage());
        return false;
    }
}

function getCurrentUser($pdo) {
    if (!isset($_SESSION['usuario_id'])) return null;
    try {
        $stmt = $pdo->prepare("SELECT u.*, a.id as alumno_id, a.curso, a.seccion, a.cedula as alumno_cedula,
            p.id as profesor_id, p.especialidad, p.cedula as profesor_cedula
            FROM usuarios u LEFT JOIN alumnos a ON u.id = a.id_usuario LEFT JOIN profesores p ON u.id = p.id_usuario
            WHERE u.id = ? AND u.activo = 1");
        $stmt->execute([$_SESSION['usuario_id']]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error al obtener usuario: " . $e->getMessage());
        return null;
    }
}

function getUnreadNotifications($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notificaciones WHERE id_usuario = ? AND leida = 0");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

function getNotifications($pdo, $userId, $limit = 10) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM notificaciones WHERE id_usuario = ? ORDER BY creado_en DESC LIMIT ?");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

function markNotificationsAsRead($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("UPDATE notificaciones SET leida = 1 WHERE id_usuario = ? AND leida = 0");
        return $stmt->execute([$userId]);
    } catch (PDOException $e) {
        return false;
    }
}

function hasChannelPermission($pdo, $userId, $channelId, $requiredRole = 'profesor') {
    try {
        $stmt = $pdo->prepare("SELECT rol FROM usuarios WHERE id = ? AND rol = 'admin'");
        $stmt->execute([$userId]);
        if ($stmt->fetch()) return true;
        
        $stmt = $pdo->prepare("SELECT pr.rol FROM profesores pr JOIN canales c ON pr.id = c.id_profesor JOIN usuarios u ON pr.id_usuario = u.id WHERE c.id = ? AND u.id = ?");
        $stmt->execute([$channelId, $userId]);
        if ($stmt->fetch() && $requiredRole === 'profesor') return true;
        
        if ($requiredRole === 'alumno') {
            $stmt = $pdo->prepare("SELECT 1 FROM inscripciones i JOIN alumnos a ON i.id_alumno = a.id JOIN usuarios u ON a.id_usuario = u.id WHERE i.id_canal = ? AND u.id = ?");
            $stmt->execute([$channelId, $userId]);
            return $stmt->fetch() !== false;
        }
        return false;
    } catch (PDOException $e) {
        return false;
    }
}

function getChannelMemberCount($pdo, $channelId) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM inscripciones WHERE id_canal = ?");
        $stmt->execute([$channelId]);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

// ── Base de datos (MySQLi) ────────────────────────────

function tienePermisoCanal(int $id_canal, int $id_usuario, string $rol): bool {
    global $conn;
    if ($rol === 'admin') return true;
    
    if ($rol === 'profesor') {
        $stmt = $conn->prepare("SELECT c.id FROM canales c INNER JOIN profesores p ON c.id_profesor = p.id WHERE c.id = ? AND p.id_usuario = ?");
        $stmt->bind_param("ii", $id_canal, $id_usuario);
        $stmt->execute();
        $stmt->store_result();
        $result = $stmt->num_rows > 0;
        $stmt->close();
        return $result;
    }
    
    if ($rol === 'alumno') {
        $stmt = $conn->prepare("SELECT i.id FROM inscripciones i INNER JOIN alumnos a ON i.id_alumno = a.id WHERE i.id_canal = ? AND a.id_usuario = ?");
        $stmt->bind_param("ii", $id_canal, $id_usuario);
        $stmt->execute();
        $stmt->store_result();
        $result = $stmt->num_rows > 0;
        $stmt->close();
        return $result;
    }
    return false;
}

function usuarioActual(): ?array {
    if (!isset($_SESSION['id_usuario'])) return null;
    global $conn;
    $stmt = $conn->prepare("SELECT id, correo, nombre_completo, rol, avatar FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['id_usuario']);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();
    $stmt->close();
    return $usuario;
}

function registrarLog(int $id_usuario, string $accion, string $detalle = null): void {
    global $conn;
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $stmt = $conn->prepare("INSERT INTO logs_actividad (id_usuario, accion, detalle, ip, user_agent) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $id_usuario, $accion, $detalle, $ip, $user_agent);
    $stmt->execute();
    $stmt->close();
}

function notificacionesNoLeidas(int $id_usuario): int {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM notificaciones WHERE id_usuario = ? AND leida = 0");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return (int) $row['total'];
}

function enviarNotificacion(int $id_usuario, string $tipo, string $titulo, string $contenido, string $url = null): void {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO notificaciones (id_usuario, tipo, titulo, contenido, url) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $id_usuario, $tipo, $titulo, $contenido, $url);
    $stmt->execute();
    $stmt->close();
}
?>