<?php
// ============================================================
// CONFIG / CONFIG.EXAMPLE.PHP — Mboa' Joaju v2.0
// Copia este archivo a config.php y ajusta los valores
// ============================================================

session_start();

// ── Configuración general ───────────────────────────────
define('APP_NAME', 'Mbo\'a Joaju');
define('APP_VERSION', '2.0.0');
define('APP_SHORT_NAME', 'Mbo\'a');

// ⚠️ CAMBIAR según dónde estés trabajando:
// - XAMPP local:    'http://localhost/mboajoaju/'
// - Red LAN:        'http://192.168.1.100/mboajoaju/'
// - Hosting:        'https://tu-dominio.com/'
define('URL_BASE', 'http://localhost/mboajoaju/');
define('BASE_URL', URL_BASE);
define('BASE_PATH', dirname(__DIR__) . '/');
define('TIMEZONE', 'America/Asuncion');

// ── Rutas de archivos ──────────────────────────────────
define('ROOT_PATH', dirname(__DIR__) . '/');
define('UPLOAD_PATH', ROOT_PATH . 'uploads/');
define('LOG_PATH', ROOT_PATH . 'logs/');

// ── Límites ─────────────────────────────────────────────
define('MAX_FILE_SIZE', 50 * 1024 * 1024);
define('MAX_POST_SIZE', 50 * 1024 * 1024);
define('UPLOAD_MAX_SIZE', 50 * 1024 * 1024);
define('SESSION_TIMEOUT', 3600);

// ── Extensiones permitidas ─────────────────────────────
define('ALLOWED_EXTENSIONS', [
    'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg', 'ico',
    'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
    'pdf', 'txt', 'rtf', 'odt', 'ods', 'odp', 'odg',
    'zip', 'rar', '7z', 'tar', 'gz',
    'mp4', 'mp3', 'wav', 'avi', 'mov', 'wmv',
    'csv', 'json', 'xml'
]);
define('UPLOAD_ALLOWED_EXT', ALLOWED_EXTENSIONS);

// ── Tipos MIME permitidos ──────────────────────────────
define('ALLOWED_MIME_TYPES', [
    'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp',
    'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'application/pdf', 'text/plain', 'text/rtf',
    'application/zip', 'application/x-rar-compressed',
    'video/mp4', 'audio/mpeg', 'audio/wav'
]);
define('UPLOAD_ALLOWED_MIME', ALLOWED_MIME_TYPES);

// ── Emojis permitidos ──────────────────────────────────
define('ALLOWED_EMOJIS', ['👍', '❤️', '😄', '👏', '🎉', '🙌', '💡', '📚', '⭐', '🔥', '👀', '💪']);

// ── Roles del sistema ──────────────────────────────────
define('ROLES', ['admin', 'profesor', 'alumno']);
define('TASK_STATUS', ['borrador', 'activa', 'cerrada']);
define('PUBLICATION_TYPES', ['anuncio', 'tarea', 'material', 'general']);

// ── Configuración de seguridad ─────────────────────────
define('SALT_ROUNDS', 12);
define('PASSWORD_MIN_LENGTH', 6);

// ════════════════════════════════════════════════════════════
// CONFIGURACIÓN DE LA BASE DE DATOS
// ════════════════════════════════════════════════════════════
define('DB_HOST', 'localhost');
define('DB_NAME', 'mboajoaju_teams');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ════════════════════════════════════════════════════════════
// INICIALIZACIÓN
// ════════════════════════════════════════════════════════════

date_default_timezone_set(TIMEZONE);

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', LOG_PATH . 'error.log');

ini_set('upload_max_filesize', '50M');
ini_set('post_max_size', '50M');
ini_set('max_execution_time', 300);

require_once ROOT_PATH . 'includes/functions.php';
require_once ROOT_PATH . 'includes/security.php';
require_once __DIR__ . '/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['creado_en'])) {
    $_SESSION['creado_en'] = time();
} elseif (time() - $_SESSION['creado_en'] > SESSION_TIMEOUT) {
    session_regenerate_id(true);
    $_SESSION['creado_en'] = time();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>