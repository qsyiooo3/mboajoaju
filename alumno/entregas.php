<?php
// ============================================================
// ALUMNO / ENTREGAS.PHP — Mboa' Joaju v2.0
// Entregar tareas y ver historial de entregas
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/upload.php';

requireRole('alumno');

$seccion = 'entregas';
$titulo = 'Mis Entregas';
$mensaje = '';
$tipo_alerta = '';

$id_usuario = (int) $_SESSION['id_usuario'];
$csrf_token = $_SESSION['csrf_token'];

// ── Obtener ID del alumno ──────────────────────────────────
$stmt = $conn->prepare("SELECT id FROM alumnos WHERE id_usuario = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();
$alumno = $result->fetch_assoc();
$stmt->close();

if (!$alumno) {
    header('Location: ' . BASE_URL . 'index.php');
    exit();
}

$id_alumno = $alumno['id'];

// ── Procesar entrega de tarea ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'entregar') {
    validarCSRF();
    
    $id_publicacion = (int) ($_POST['id_publicacion'] ?? 0);
    $comentario = trim($_POST['comentario'] ?? '');
    
    // Verificar que la publicación existe y es una tarea
    $stmt = $conn->prepare(
        "SELECT p.id, p.titulo, c.id AS canal_id 
         FROM publicaciones p 
         INNER JOIN canales c ON p.id_canal = c.id 
         INNER JOIN inscripciones i ON i.id_canal = c.id 
         WHERE p.id = ? AND p.tipo_publicacion = 'tarea' AND i.id_alumno = ?
         LIMIT 1"
    );
    $stmt->bind_param("ii", $id_publicacion, $id_alumno);
    $stmt->execute();
    $result = $stmt->get_result();
    $tarea = $result->fetch_assoc();
    $stmt->close();
    
    if (!$tarea) {
        $mensaje = 'No tienes permiso para entregar esta tarea.';
        $tipo_alerta = 'danger';
    } else {
        // Verificar que no haya entregado ya
        $stmt = $conn->prepare("SELECT id FROM entregas WHERE id_publicacion = ? AND id_alumno = ?");
        $stmt->bind_param("ii", $id_publicacion, $id_alumno);
        $stmt->execute();
        $stmt->store_result();
        $ya_entregue = $stmt->num_rows > 0;
        $stmt->close();
        
        if ($ya_entregue) {
            $mensaje = 'Ya entregaste esta tarea. Puedes re-entregar subiendo un nuevo archivo.';
            $tipo_alerta = 'warning';
        } else {
            // Subir archivo
            if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
                $mensaje = 'Debes subir un archivo para entregar la tarea.';
                $tipo_alerta = 'danger';
            } else {
                $resultado = subirArchivo($_FILES['archivo'], 'entregas');
                
                if (!$resultado['exito']) {
                    $mensaje = 'Error al subir el archivo: ' . $resultado['mensaje'];
                    $tipo_alerta = 'danger';
                } else {
                    $archivo_url = $resultado['ruta'];
                    $archivo_nombre = $resultado['nombre_original'];
                    
                    $stmt = $conn->prepare(
                        "INSERT INTO entregas (id_publicacion, id_alumno, archivo_url, archivo_nombre, comentario) 
                         VALUES (?, ?, ?, ?, ?)"
                    );
                    $stmt->bind_param("iisss", $id_publicacion, $id_alumno, $archivo_url, $archivo_nombre, $comentario);
                    
                    if ($stmt->execute()) {
                        registrarLog($_SESSION['id_usuario'], 'entregar_tarea', "Entregó tarea: " . $tarea['titulo']);
                        $mensaje = "✅ Tarea entregada correctamente.";
                        $tipo_alerta = 'success';
                    } else {
                        $mensaje = 'Error al registrar la entrega.';
                        $tipo_alerta = 'danger';
                        error_log("Error entrega: " . $conn->error);
                    }
                    $stmt->close();
                }
            }
        }
    }
}

// ── Obtener tareas pendientes ─────────────────────────────
$stmt = $conn->prepare(
    "SELECT p.id, p.titulo, p.contenido, p.fecha_limite, 
            c.nombre AS canal_nombre, u.nombre_completo AS profesor_nombre
     FROM publicaciones p 
     INNER JOIN canales c ON p.id_canal = c.id 
     INNER JOIN inscripciones i ON i.id_canal = c.id 
     INNER JOIN profesores pr ON p.id_profesor = pr.id
     INNER JOIN usuarios u ON pr.id_usuario = u.id
     WHERE i.id_alumno = ? 
       AND p.tipo_publicacion = 'tarea' 
       AND (p.fecha_limite IS NULL OR p.fecha_limite > NOW())
       AND NOT EXISTS (
           SELECT 1 FROM entregas e 
           WHERE e.id_publicacion = p.id AND e.id_alumno = ?
       )
     ORDER BY p.fecha_limite ASC"
);
$stmt->bind_param("ii", $id_alumno, $id_alumno);
$stmt->execute();
$tareas_pendientes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ── Obtener historial de entregas ─────────────────────────
$stmt = $conn->prepare(
    "SELECT e.*, p.titulo AS tarea_titulo, c.nombre AS canal_nombre,
            u.nombre_completo AS profesor_nombre
     FROM entregas e 
     INNER JOIN publicaciones p ON e.id_publicacion = p.id 
     INNER JOIN canales c ON p.id_canal = c.id 
     INNER JOIN profesores pr ON p.id_profesor = pr.id
     INNER JOIN usuarios u ON pr.id_usuario = u.id
     WHERE e.id_alumno = ? 
     ORDER BY e.fecha_entrega DESC"
);
$stmt->bind_param("i", $id_alumno);
$stmt->execute();
$historial_entregas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar_alumno.php';
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

        <!-- Tareas Pendientes -->
        <div class="card">
            <h3>⏳ Tareas Pendientes</h3>
            <?php if (!empty($tareas_pendientes)): ?>
                <?php foreach ($tareas_pendientes as $tarea): ?>
                    <div class="tarea-pendiente">
                        <div class="tarea-info">
                            <h4><?= h($tarea['titulo']) ?></h4>
                            <span class="badge badge-secondary">📺 <?= h($tarea['canal_nombre']) ?></span>
                            <span class="badge badge-secondary">👨‍🏫 <?= h($tarea['profesor_nombre']) ?></span>
                            <?php if ($tarea['fecha_limite']): ?>
                                <span class="badge badge-warning">⏰ Límite: <?= fecha($tarea['fecha_limite']) ?></span>
                            <?php endif; ?>
                            <p><?= h(truncar($tarea['descripcion'] ?? '', 150)) ?></p>
                        </div>
                        <div class="tarea-accion">
                            <button class="btn-primary" onclick="abrirModalEntrega(<?= $tarea['id'] ?>, '<?= h($tarea['titulo']) ?>')">
                                📤 Entregar
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color:#888;text-align:center;">🎉 No tienes tareas pendientes.</p>
            <?php endif; ?>
        </div>

        <!-- Historial de Entregas -->
        <div class="card">
            <h3>📥 Historial de Entregas</h3>
            <?php if (!empty($historial_entregas)): ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Tarea</th>
                                <th>Canal</th>
                                <th>Fecha Entrega</th>
                                <th>Archivo</th>
                                <th>Calificación</th>
                                <th>Observación</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historial_entregas as $h): ?>
                                <tr>
                                    <td><strong><?= h($h['tarea_titulo']) ?></strong></td>
                                    <td><?= h($h['canal_nombre']) ?></td>
                                    <td style="font-size:12px;color:#888;"><?= fecha($h['fecha_entrega']) ?></td>
                                    <td>
                                        <a href="<?= BASE_URL . $h['archivo_url'] ?>" target="_blank" class="btn-sm btn-info">📎 Ver</a>
                                    </td>
                                    <td>
                                        <?php if ($h['calificacion'] !== null): ?>
                                            <span class="badge badge-success"><?= number_format($h['calificacion'], 1) ?>/10</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">⏳ Pendiente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= h($h['observacion_docente'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="color:#888;text-align:center;">No has entregado ninguna tarea aún.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Entregar Tarea -->
<div class="modal-overlay" id="modalEntrega">
    <div class="modal-content" style="max-width:500px;">
        <button class="close-modal" onclick="cerrarModalEntrega()">✕</button>
        <h2>📤 Entregar Tarea</h2>
        <p id="tareaNombre" style="color:var(--color-text-light);margin-bottom:16px;"></p>
        
        <form action="<?= BASE_URL ?>alumno/entregas.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <input type="hidden" name="accion" value="entregar">
            <input type="hidden" name="id_publicacion" id="publicacionId" value="0">
            
            <div class="form-group">
                <label>Archivo de Entrega *</label>
                <input type="file" name="archivo" required accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.zip">
                <small class="help-text">Máx. <?= formatearBytes(UPLOAD_MAX_SIZE) ?> - PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, JPG, PNG, ZIP</small>
            </div>
            
            <div class="form-group">
                <label>Comentario (opcional)</label>
                <textarea name="comentario" rows="3" placeholder="Agrega un comentario sobre tu entrega..."></textarea>
            </div>
            
            <button type="submit" class="btn-primary" style="width:100%;">📤 Entregar Tarea</button>
        </form>
    </div>
</div>

<style>
.tarea-pendiente {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 16px;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    margin-bottom: 12px;
    gap: 16px;
    flex-wrap: wrap;
}
.tarea-info h4 {
    margin: 0 0 4px 0;
    font-size: var(--font-size-md);
}
.tarea-info p {
    margin: 6px 0 0 0;
    font-size: var(--font-size-sm);
    color: var(--color-text-light);
}
.tarea-info .badge {
    margin-right: 4px;
}
.tarea-accion {
    flex-shrink: 0;
}
</style>

<script>
function abrirModalEntrega(id, titulo) {
    document.getElementById('publicacionId').value = id;
    document.getElementById('tareaNombre').innerHTML = '📝 <strong>' + titulo + '</strong>';
    document.getElementById('modalEntrega').classList.add('activo');
}

function cerrarModalEntrega() {
    document.getElementById('modalEntrega').classList.remove('activo');
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') cerrarModalEntrega();
});

document.getElementById('modalEntrega').addEventListener('click', function(e) {
    if (e.target === this) cerrarModalEntrega();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>