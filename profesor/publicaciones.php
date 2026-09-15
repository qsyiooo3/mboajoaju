<?php
// ============================================================
// PROFESOR / PUBLICACIONES.PHP — Mboa' Joaju v2.0
// Crear y gestionar publicaciones del profesor
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/upload.php';

requireRole('profesor');

$seccion = 'publicaciones';
$titulo = 'Mis Publicaciones';
$mensaje = '';
$tipo_alerta = '';

$id_usuario = (int) $_SESSION['id_usuario'];
$csrf_token = $_SESSION['csrf_token'];

// ── Obtener ID del profesor ───────────────────────────────
$stmt = $conn->prepare("SELECT id FROM profesores WHERE id_usuario = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();
$profesor = $result->fetch_assoc();
$stmt->close();

if (!$profesor) {
    header('Location: ' . BASE_URL . 'index.php');
    exit();
}

$id_profesor = $profesor['id'];

// ── Obtener canales del profesor ──────────────────────────
$stmt = $conn->prepare(
    "SELECT id, nombre FROM canales WHERE id_profesor = ? AND activo = 1 ORDER BY nombre"
);
$stmt->bind_param("i", $id_profesor);
$stmt->execute();
$canales_profesor = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ── Procesar creación de publicación ──────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'crear_publicacion') {
    validarCSRF();
    
    $id_canal = (int) ($_POST['id_canal'] ?? 0);
    $titulo = trim($_POST['titulo'] ?? '');
    $contenido = trim($_POST['contenido'] ?? '');
    $tipo_publicacion = $_POST['tipo_publicacion'] ?? 'general';
    $fecha_limite = $_POST['fecha_limite'] ?? null;
    
    // Validaciones
    $errores = [];
    if ($id_canal === 0) $errores[] = 'Selecciona un canal.';
    if (strlen($titulo) < 3) $errores[] = 'El título debe tener al menos 3 caracteres.';
    if (strlen($contenido) < 3) $errores[] = 'El contenido debe tener al menos 3 caracteres.';
    
    // Verificar que el canal pertenezca al profesor
    $stmt = $conn->prepare("SELECT id FROM canales WHERE id = ? AND id_profesor = ?");
    $stmt->bind_param("ii", $id_canal, $id_profesor);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) {
        $errores[] = 'No tienes permiso para publicar en este canal.';
    }
    $stmt->close();
    
    if (empty($errores)) {
        // Subir archivo si existe
        $archivo_url = null;
        $archivo_nombre = null;
        $tipo_archivo = null;
        
        if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
            $resultado = subirArchivo($_FILES['archivo'], 'publicaciones');
            if ($resultado['exito']) {
                $archivo_url = $resultado['ruta'];
                $archivo_nombre = $resultado['nombre_original'];
                $tipo_archivo = $resultado['tipo'];
            } else {
                $mensaje = 'Error al subir el archivo: ' . $resultado['mensaje'];
                $tipo_alerta = 'danger';
            }
        }
        
        if (empty($mensaje)) {
            $stmt = $conn->prepare(
                "INSERT INTO publicaciones 
                 (id_canal, id_profesor, titulo, contenido, archivo_url, archivo_nombre, tipo_archivo, tipo_publicacion, fecha_limite) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param(
                "iisssssss", 
                $id_canal, 
                $id_profesor, 
                $titulo, 
                $contenido, 
                $archivo_url, 
                $archivo_nombre, 
                $tipo_archivo, 
                $tipo_publicacion, 
                $fecha_limite
            );
            
            if ($stmt->execute()) {
                registrarLog($_SESSION['id_usuario'], 'crear_publicacion', "Publicó: $titulo");
                $mensaje = "✅ Publicación creada correctamente.";
                $tipo_alerta = 'success';
            } else {
                $mensaje = 'Error al crear la publicación.';
                $tipo_alerta = 'danger';
                error_log("Error crear publicación: " . $conn->error);
            }
            $stmt->close();
        }
    } else {
        $mensaje = implode(' ', $errores);
        $tipo_alerta = 'danger';
    }
}

// ── Obtener publicaciones del profesor ────────────────────
$canal_filtro = isset($_GET['canal']) ? (int) $_GET['canal'] : 0;

$sql = "SELECT p.*, c.nombre AS canal_nombre 
        FROM publicaciones p 
        INNER JOIN canales c ON p.id_canal = c.id 
        WHERE p.id_profesor = ?";
$params = [$id_profesor];
$types = "i";

if ($canal_filtro > 0) {
    $sql .= " AND p.id_canal = ?";
    $params[] = $canal_filtro;
    $types .= "i";
}

$sql .= " ORDER BY p.fecha_creacion DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$publicaciones = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar_profesor.php';
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

        <!-- Botón Crear Publicación -->
        <div class="toolbar">
            <button class="btn-primary" onclick="abrirModalPublicacion()">📝 Crear Publicación</button>
            <?php if (!empty($canales_profesor)): ?>
                <select id="filterCanal" onchange="filtrarPorCanal()" style="padding:8px 12px;border:1px solid var(--color-border);border-radius:var(--radius-sm);">
                    <option value="">Todos los canales</option>
                    <?php foreach ($canales_profesor as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($canal_filtro == $c['id']) ? 'selected' : '' ?>>
                            <?= h($c['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
        </div>

        <!-- Lista de Publicaciones -->
        <div class="card">
            <h3>📝 Mis Publicaciones</h3>
            <?php if (!empty($publicaciones)): ?>
                <?php foreach ($publicaciones as $pub): ?>
                    <div class="publicacion-item">
                        <div class="pub-header">
                            <div class="pub-titulo">
                                <span class="badge <?= $pub['tipo_publicacion'] === 'tarea' ? 'badge-warning' : 'badge-info' ?>">
                                    <?= ucfirst($pub['tipo_publicacion']) ?>
                                </span>
                                <strong><?= h($pub['titulo']) ?></strong>
                            </div>
                            <span class="pub-fecha"><?= fecha($pub['fecha_creacion']) ?></span>
                        </div>
                        <div class="pub-body">
                            <p><?= nl2br(h($pub['contenido'])) ?></p>
                            <div class="pub-meta">
                                <span class="badge badge-secondary">📺 <?= h($pub['canal_nombre']) ?></span>
                                <?php if (!empty($pub['archivo_url'])): ?>
                                    <a href="<?= BASE_URL . $pub['archivo_url'] ?>" target="_blank" class="badge badge-info">
                                        📎 <?= h($pub['archivo_nombre'] ?? 'Descargar') ?>
                                    </a>
                                <?php endif; ?>
                                <?php if ($pub['tipo_publicacion'] === 'tarea' && $pub['fecha_limite']): ?>
                                    <span class="badge badge-warning">⏰ Límite: <?= fecha($pub['fecha_limite']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color:#888;text-align:center;padding:30px 0;">
                    No has publicado nada aún.<br>
                    <button class="btn-primary" onclick="abrirModalPublicacion()" style="margin-top:10px;">📝 Crear tu primera publicación</button>
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Crear Publicación -->
<div class="modal-overlay" id="modalPublicacion">
    <div class="modal-content" style="max-width:600px;">
        <button class="close-modal" onclick="cerrarModalPublicacion()">✕</button>
        <h2>📝 Crear Publicación</h2>
        
        <form action="<?= BASE_URL ?>profesor/publicaciones.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <input type="hidden" name="accion" value="crear_publicacion">
            
            <div class="form-group">
                <label>Canal *</label>
                <select name="id_canal" required>
                    <option value="">Seleccionar canal...</option>
                    <?php foreach ($canales_profesor as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= h($c['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($canales_profesor)): ?>
                    <small style="color:var(--color-error);">⚠️ No tienes canales. Crea uno primero en "Mis Canales".</small>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label>Título *</label>
                <input type="text" name="titulo" required placeholder="Ej: Material de la clase 1" maxlength="200">
            </div>
            
            <div class="form-group">
                <label>Tipo de Publicación</label>
                <select name="tipo_publicacion">
                    <option value="general">General</option>
                    <option value="anuncio">Anuncio</option>
                    <option value="material">Material</option>
                    <option value="tarea">Tarea</option>
                </select>
            </div>
            
            <div class="form-group" id="grupo_fecha_limite" style="display:none;">
                <label>Fecha Límite (para tareas)</label>
                <input type="datetime-local" name="fecha_limite">
            </div>
            
            <div class="form-group">
                <label>Contenido *</label>
                <textarea name="contenido" required rows="5" placeholder="Escribe el contenido de tu publicación..."></textarea>
            </div>
            
            <div class="form-group">
                <label>Archivo Adjunto (opcional)</label>
                <input type="file" name="archivo" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.zip">
                <small class="help-text">Máx. <?= formatearBytes(UPLOAD_MAX_SIZE) ?> - PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, JPG, PNG, ZIP</small>
            </div>
            
            <button type="submit" class="btn-primary" style="width:100%;">📤 Publicar</button>
        </form>
    </div>
</div>

<style>
.publicacion-item {
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    padding: 16px 20px;
    margin-bottom: 14px;
    transition: box-shadow var(--transition-fast);
}
.publicacion-item:hover {
    box-shadow: 0 2px 12px var(--color-shadow);
}
.pub-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 8px;
}
.pub-titulo {
    display: flex;
    align-items: center;
    gap: 8px;
}
.pub-titulo strong {
    font-size: var(--font-size-md);
}
.pub-fecha {
    font-size: var(--font-size-xs);
    color: var(--color-text-light);
}
.pub-body p {
    margin: 0 0 10px 0;
    font-size: var(--font-size-sm);
    color: var(--color-text);
    line-height: 1.6;
}
.pub-meta {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
</style>

<script>
function abrirModalPublicacion() {
    document.getElementById('modalPublicacion').classList.add('activo');
}

function cerrarModalPublicacion() {
    document.getElementById('modalPublicacion').classList.remove('activo');
}

document.querySelector('select[name="tipo_publicacion"]').addEventListener('change', function() {
    const grupo = document.getElementById('grupo_fecha_limite');
    grupo.style.display = this.value === 'tarea' ? 'block' : 'none';
});

function filtrarPorCanal() {
    const canal = document.getElementById('filterCanal').value;
    const url = new URL(window.location.href);
    if (canal) {
        url.searchParams.set('canal', canal);
    } else {
        url.searchParams.delete('canal');
    }
    window.location.href = url.toString();
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') cerrarModalPublicacion();
});

document.getElementById('modalPublicacion').addEventListener('click', function(e) {
    if (e.target === this) cerrarModalPublicacion();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>