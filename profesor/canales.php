<?php
// ============================================================
// PROFESOR / CANALES.PHP — Mboa' Joaju v2.0
// Gestión de canales del profesor (solo sus canales)
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('profesor');

$seccion = 'canales';
$titulo = 'Mis Canales';
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

// ── Procesar creación de canal ────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'crear_canal') {
    validarCSRF();
    
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $curso = trim($_POST['curso'] ?? '');
    $seccion_canal = trim($_POST['seccion'] ?? '');
    
    if (strlen($nombre) < 3) {
        $mensaje = 'El nombre del canal debe tener al menos 3 caracteres.';
        $tipo_alerta = 'danger';
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO canales (nombre, descripcion, id_profesor, curso, seccion) 
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("ssiss", $nombre, $descripcion, $id_profesor, $curso, $seccion_canal);
        
        if ($stmt->execute()) {
            $id_canal = $conn->insert_id;
            registrarLog($_SESSION['id_usuario'], 'crear_canal', "Creó canal: $nombre");
            $mensaje = "✅ Canal '$nombre' creado correctamente.";
            $tipo_alerta = 'success';
        } else {
            $mensaje = 'Error al crear el canal.';
            $tipo_alerta = 'danger';
            error_log("Error crear canal: " . $conn->error);
        }
        $stmt->close();
    }
}

// ── Procesar eliminación de canal (SOLO ADMIN PUEDE) ────
// Los profesores NO pueden eliminar canales
// (Esta funcionalidad solo está en admin)

// ── Obtener canales del profesor ──────────────────────────
$stmt = $conn->prepare(
    "SELECT c.*, 
            (SELECT COUNT(*) FROM inscripciones i WHERE i.id_canal = c.id) AS total_alumnos,
            (SELECT COUNT(*) FROM publicaciones p WHERE p.id_canal = c.id) AS total_publicaciones
     FROM canales c
     WHERE c.id_profesor = ? AND c.activo = 1
     ORDER BY c.fecha_creacion DESC"
);
$stmt->bind_param("i", $id_profesor);
$stmt->execute();
$canales = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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

        <!-- Botón Crear Canal -->
        <div class="toolbar">
            <button class="btn-primary" onclick="abrirModalCanal()">➕ Crear Nuevo Canal</button>
            <div class="search-box">
                <input type="text" id="searchCanal" placeholder="🔍 Buscar canal..." onkeyup="filtrarTabla('searchCanal', 'tablaCanales')">
            </div>
        </div>

        <!-- Lista de Canales -->
        <div class="card">
            <h3>📺 Mis Canales</h3>
            <?php if (!empty($canales)): ?>
                <div class="canales-grid">
                    <?php foreach ($canales as $c): ?>
                        <div class="canal-card">
                            <div class="canal-header" style="border-left: 4px solid <?= h($c['color'] ?? '#1a4a2a') ?>;">
                                <span class="canal-icon"><?= h($c['icono'] ?? '📚') ?></span>
                                <div>
                                    <h4><?= h($c['nombre']) ?></h4>
                                    <span class="canal-meta">
                                        <?= h($c['curso'] ?? '') ?> - <?= h($c['seccion'] ?? '') ?>
                                    </span>
                                </div>
                            </div>
                            <div class="canal-body">
                                <p><?= h(truncar($c['descripcion'] ?? '', 100)) ?></p>
                                <div class="canal-stats">
                                    <span class="badge badge-info">🎓 <?= $c['total_alumnos'] ?> alumnos</span>
                                    <span class="badge badge-primary">📝 <?= $c['total_publicaciones'] ?> publicaciones</span>
                                </div>
                            </div>
                            <div class="canal-actions">
                                <a href="<?= BASE_URL ?>profesor/publicaciones.php?canal=<?= $c['id'] ?>" class="btn-sm btn-primary">📝 Ver Publicaciones</a>
                                <a href="<?= BASE_URL ?>profesor/alumnos.php?canal=<?= $c['id'] ?>" class="btn-sm btn-info">👥 Alumnos</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color:#888;text-align:center;padding:40px 0;">
                    No tienes canales aún. <br>
                    <button class="btn-primary" onclick="abrirModalCanal()" style="margin-top:10px;">➕ Crear tu primer canal</button>
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Crear Canal -->
<div class="modal-overlay" id="modalCanal">
    <div class="modal-content">
        <button class="close-modal" onclick="cerrarModalCanal()">✕</button>
        <h2>📺 Crear Nuevo Canal</h2>
        
        <form action="<?= BASE_URL ?>profesor/canales.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <input type="hidden" name="accion" value="crear_canal">
            
            <div class="form-group">
                <label>Nombre del Canal *</label>
                <input type="text" name="nombre" required placeholder="Ej: Agronomía I" maxlength="100">
            </div>
            
            <div class="form-group">
                <label>Descripción</label>
                <textarea name="descripcion" rows="3" placeholder="Descripción del canal..."></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Curso</label>
                    <select name="curso">
                        <option value="">Seleccionar...</option>
                        <option value="1ro">1ro</option>
                        <option value="2do">2do</option>
                        <option value="3ro">3ro</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Sección</label>
                    <select name="seccion">
                        <option value="">Seleccionar...</option>
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                    </select>
                </div>
            </div>
            
            <button type="submit" class="btn-primary" style="width:100%;">Crear Canal</button>
        </form>
    </div>
</div>

<style>
.canales-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 16px;
    margin-top: 12px;
}
.canal-card {
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    overflow: hidden;
    transition: box-shadow var(--transition-fast);
}
.canal-card:hover {
    box-shadow: 0 4px 16px var(--color-shadow);
}
.canal-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    background: var(--color-background);
}
.canal-icon {
    font-size: 24px;
}
.canal-header h4 {
    margin: 0;
    font-size: var(--font-size-md);
}
.canal-meta {
    font-size: var(--font-size-xs);
    color: var(--color-text-light);
}
.canal-body {
    padding: 14px 16px;
}
.canal-body p {
    margin: 0 0 10px 0;
    font-size: var(--font-size-sm);
    color: var(--color-text-light);
}
.canal-stats {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.canal-actions {
    padding: 12px 16px;
    border-top: 1px solid var(--color-border-light);
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}
</style>

<script>
function abrirModalCanal() {
    document.getElementById('modalCanal').classList.add('activo');
}

function cerrarModalCanal() {
    document.getElementById('modalCanal').classList.remove('activo');
}

function filtrarTabla(inputId, tablaId) {
    const input = document.getElementById(inputId);
    const filter = input.value.toUpperCase();
    const items = document.querySelectorAll('.canal-card');
    items.forEach(item => {
        const text = item.textContent || item.innerText;
        item.style.display = text.toUpperCase().indexOf(filter) > -1 ? '' : 'none';
    });
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') cerrarModalCanal();
});

document.getElementById('modalCanal').addEventListener('click', function(e) {
    if (e.target === this) cerrarModalCanal();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>