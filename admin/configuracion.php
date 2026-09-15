<?php
// ============================================================
// ADMIN / CONFIGURACION.PHP — Mboa' Joaju v2.0
// Configuración del sistema
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

$seccion = 'configuracion';
$titulo = 'Configuración del Sistema';
$mensaje = '';
$tipo_alerta = '';

$csrf_token = $_SESSION['csrf_token'];

// ── Procesar cambios de configuración ─────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    validarCSRF();
    
    $accion = $_POST['accion'];
    
    if ($accion === 'actualizar_sistema') {
        // Aquí se pueden guardar configuraciones en la base de datos
        // Por ahora solo mostramos mensaje de éxito
        $mensaje = 'Configuración actualizada correctamente.';
        $tipo_alerta = 'success';
        registrarLog($_SESSION['id_usuario'], 'actualizar_configuracion', 'Actualizó configuración del sistema');
    }
}

// ── Estadísticas generales ─────────────────────────────────
$stmt = $conn->query("SELECT COUNT(*) AS total FROM usuarios");
$total_usuarios = $stmt->fetch_assoc()['total'];

$stmt = $conn->query("SELECT COUNT(*) AS total FROM canales");
$total_canales = $stmt->fetch_assoc()['total'];

$stmt = $conn->query("SELECT COUNT(*) AS total FROM publicaciones");
$total_publicaciones = $stmt->fetch_assoc()['total'];

$stmt = $conn->query("SELECT COUNT(*) AS total FROM entregas");
$total_entregas = $stmt->fetch_assoc()['total'];

// ── Espacio en disco usado ─────────────────────────────────
function obtenerTamanoCarpeta($carpeta) {
    $tamano = 0;
    $carpeta = BASE_PATH . $carpeta;
    if (is_dir($carpeta)) {
        $archivos = scandir($carpeta);
        foreach ($archivos as $archivo) {
            if ($archivo !== '.' && $archivo !== '..') {
                $ruta = $carpeta . '/' . $archivo;
                if (is_file($ruta)) {
                    $tamano += filesize($ruta);
                } elseif (is_dir($ruta)) {
                    $tamano += obtenerTamanoCarpeta($carpeta . '/' . $archivo);
                }
            }
        }
    }
    return $tamano;
}

$tamano_uploads = obtenerTamanoCarpeta('uploads');
$tamano_logs = obtenerTamanoCarpeta('logs');

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar_admin.php';
?>

<div class="main-content">
    <div class="topbar">
        <h1><?= $titulo ?></h1>
        <div class="user-welcome">Bienvenido, <strong><?= h($_SESSION['nombre']) ?></strong></div>
    </div>

    <div class="content-body">
        <?php if (!empty($mensaje)): ?>
            <?= mostrarAlerta($mensaje, $tipo_alerta) ?>
        <?php endif; ?>

        <!-- Resumen del sistema -->
        <div class="stats-grid">
            <div class="stat-card stat-primary">
                <div class="stat-icon">👥</div>
                <div class="stat-info">
                    <span class="stat-value"><?= $total_usuarios ?></span>
                    <span class="stat-label">Usuarios</span>
                </div>
            </div>
            <div class="stat-card stat-success">
                <div class="stat-icon">📺</div>
                <div class="stat-info">
                    <span class="stat-value"><?= $total_canales ?></span>
                    <span class="stat-label">Canales</span>
                </div>
            </div>
            <div class="stat-card stat-info">
                <div class="stat-icon">📝</div>
                <div class="stat-info">
                    <span class="stat-value"><?= $total_publicaciones ?></span>
                    <span class="stat-label">Publicaciones</span>
                </div>
            </div>
            <div class="stat-card stat-warning">
                <div class="stat-icon">📥</div>
                <div class="stat-info">
                    <span class="stat-value"><?= $total_entregas ?></span>
                    <span class="stat-label">Entregas</span>
                </div>
            </div>
            <div class="stat-card stat-purple">
                <div class="stat-icon">💾</div>
                <div class="stat-info">
                    <span class="stat-value"><?= formatearBytes($tamano_uploads) ?></span>
                    <span class="stat-label">Archivos Subidos</span>
                </div>
            </div>
            <div class="stat-card stat-secondary">
                <div class="stat-icon">📋</div>
                <div class="stat-info">
                    <span class="stat-value"><?= formatearBytes($tamano_logs) ?></span>
                    <span class="stat-label">Logs</span>
                </div>
            </div>
        </div>

        <!-- Configuración del sistema -->
        <div class="card">
            <h3>⚙️ Configuración General</h3>
            <form action="<?= BASE_URL ?>admin/configuracion.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <input type="hidden" name="accion" value="actualizar_sistema">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Nombre del Sistema</label>
                        <input type="text" value="Mboa' Joaju" class="form-control" disabled>
                        <small class="help-text">Para cambiar, editar el archivo config/config.php</small>
                    </div>
                    <div class="form-group">
                        <label>URL Base</label>
                        <input type="text" value="<?= BASE_URL ?>" class="form-control" disabled>
                        <small class="help-text">Para cambiar, editar el archivo config/config.php</small>
                    </div>
                    <div class="form-group">
                        <label>Tamaño Máximo de Archivos</label>
                        <input type="text" value="<?= formatearBytes(UPLOAD_MAX_SIZE) ?>" class="form-control" disabled>
                        <small class="help-text">Para cambiar, editar config/config.php</small>
                    </div>
                    <div class="form-group">
                        <label>Extensiones Permitidas</label>
                        <input type="text" value="<?= implode(', ', UPLOAD_ALLOWED_EXT) ?>" class="form-control" disabled>
                        <small class="help-text">Para cambiar, editar config/config.php</small>
                    </div>
                </div>
                
                <button type="submit" class="btn-primary">💾 Guardar Configuración</button>
            </form>
        </div>

        <!-- Información del servidor -->
        <div class="card">
            <h3>🖥️ Información del Servidor</h3>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">PHP Version</span>
                    <span class="info-value"><?= phpversion() ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">MySQL Version</span>
                    <span class="info-value"><?= $conn->server_info ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Sistema Operativo</span>
                    <span class="info-value"><?= php_uname('s') ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Memoria Límite</span>
                    <span class="info-value"><?= ini_get('memory_limit') ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Tiempo Máximo de Ejecución</span>
                    <span class="info-value"><?= ini_get('max_execution_time') ?> segundos</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Fecha/Hora Servidor</span>
                    <span class="info-value"><?= date('d/m/Y H:i:s') ?></span>
                </div>
            </div>
        </div>

        <!-- Acciones del sistema -->
        <div class="card">
            <h3>🛠️ Acciones del Sistema</h3>
            <div class="action-buttons">
                <a href="<?= BASE_URL ?>admin/configuracion.php?accion=limpiar_cache" 
                   class="btn-secondary" 
                   onclick="return confirm('¿Limpiar caché del sistema?')">
                   🧹 Limpiar Caché
                </a>
                <a href="<?= BASE_URL ?>admin/configuracion.php?accion=backup" 
                   class="btn-secondary" 
                   onclick="return confirm('¿Descargar respaldo de la base de datos?')">
                   💾 Respaldo BD
                </a>
                <a href="<?= BASE_URL ?>admin/configuracion.php?accion=logs" 
                   class="btn-secondary">
                   📋 Ver Logs
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 12px;
}
.info-item {
    display: flex;
    justify-content: space-between;
    padding: 10px 14px;
    background: var(--color-background);
    border-radius: var(--radius-sm);
    border-left: 3px solid var(--color-primary);
}
.info-label {
    font-weight: 600;
    color: var(--color-text-light);
    font-size: var(--font-size-sm);
}
.info-value {
    font-weight: 500;
    color: var(--color-text);
    font-size: var(--font-size-sm);
}
.action-buttons {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}
.btn-secondary {
    padding: 10px 18px;
    background: var(--color-secondary);
    color: white;
    border: none;
    border-radius: var(--radius-sm);
    font-size: var(--font-size-sm);
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: background var(--transition-fast);
}
.btn-secondary:hover {
    background: var(--color-secondary-dark);
}
.mini-stats .stat-card {
    padding: 14px 20px;
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>