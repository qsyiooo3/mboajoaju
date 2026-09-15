<?php
// ============================================================
// PROFESOR / ENTREGAS.PHP — Mboa' Joaju v2.0
// Ver y calificar entregas de alumnos
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('profesor');

$seccion = 'entregas';
$titulo = 'Entregas de Alumnos';
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

// ── Procesar calificación ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'calificar') {
    validarCSRF();
    
    $id_entrega = (int) ($_POST['id_entrega'] ?? 0);
    $calificacion = (float) ($_POST['calificacion'] ?? 0);
    $observacion = trim($_POST['observacion'] ?? '');
    
    if ($calificacion < 0 || $calificacion > 10) {
        $mensaje = 'La calificación debe estar entre 0 y 10.';
        $tipo_alerta = 'danger';
    } else {
        // Verificar que la entrega pertenece a un canal del profesor
        $stmt = $conn->prepare(
            "SELECT e.id FROM entregas e 
             INNER JOIN publicaciones p ON e.id_publicacion = p.id 
             INNER JOIN canales c ON p.id_canal = c.id 
             WHERE e.id = ? AND c.id_profesor = ?"
        );
        $stmt->bind_param("ii", $id_entrega, $id_profesor);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows === 0) {
            $mensaje = 'No tienes permiso para calificar esta entrega.';
            $tipo_alerta = 'danger';
        } else {
            $stmt->close();
            
            $stmt = $conn->prepare(
                "UPDATE entregas SET calificacion = ?, observacion_docente = ? WHERE id = ?"
            );
            $stmt->bind_param("dsi", $calificacion, $observacion, $id_entrega);
            
            if ($stmt->execute()) {
                registrarLog($_SESSION['id_usuario'], 'calificar_entrega', "Calificó entrega ID: $id_entrega con $calificacion");
                $mensaje = "✅ Calificación registrada correctamente.";
                $tipo_alerta = 'success';
            } else {
                $mensaje = 'Error al registrar la calificación.';
                $tipo_alerta = 'danger';
                error_log("Error calificar: " . $conn->error);
            }
            $stmt->close();
        }
    }
}

// ── Obtener entregas de los canales del profesor ──────────
$canal_filtro = isset($_GET['canal']) ? (int) $_GET['canal'] : 0;
$estado_filtro = $_GET['estado'] ?? 'todos';

$sql = "SELECT e.*, 
        u.nombre_completo AS alumno_nombre, 
        p.titulo AS tarea_titulo, 
        c.nombre AS canal_nombre,
        c.id AS canal_id
        FROM entregas e 
        INNER JOIN publicaciones p ON e.id_publicacion = p.id 
        INNER JOIN canales c ON p.id_canal = c.id 
        INNER JOIN alumnos a ON e.id_alumno = a.id 
        INNER JOIN usuarios u ON a.id_usuario = u.id 
        WHERE c.id_profesor = ?";
$params = [$id_profesor];
$types = "i";

if ($canal_filtro > 0) {
    $sql .= " AND c.id = ?";
    $params[] = $canal_filtro;
    $types .= "i";
}

if ($estado_filtro === 'pendientes') {
    $sql .= " AND e.calificacion IS NULL";
} elseif ($estado_filtro === 'calificadas') {
    $sql .= " AND e.calificacion IS NOT NULL";
}

$sql .= " ORDER BY e.fecha_entrega DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$entregas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ── Canales del profesor para filtro ──────────────────────
$stmt = $conn->prepare("SELECT id, nombre FROM canales WHERE id_profesor = ? AND activo = 1 ORDER BY nombre");
$stmt->bind_param("i", $id_profesor);
$stmt->execute();
$canales_profesor = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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

        <!-- Filtros -->
        <div class="toolbar">
            <div class="filter-group">
                <select id="filterCanal" onchange="aplicarFiltros()">
                    <option value="">Todos los canales</option>
                    <?php foreach ($canales_profesor as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($canal_filtro == $c['id']) ? 'selected' : '' ?>>
                            <?= h($c['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select id="filterEstado" onchange="aplicarFiltros()">
                    <option value="todos" <?= $estado_filtro === 'todos' ? 'selected' : '' ?>>Todos</option>
                    <option value="pendientes" <?= $estado_filtro === 'pendientes' ? 'selected' : '' ?>>Pendientes</option>
                    <option value="calificadas" <?= $estado_filtro === 'calificadas' ? 'selected' : '' ?>>Calificadas</option>
                </select>
            </div>
            <div class="search-box">
                <input type="text" id="searchEntrega" placeholder="🔍 Buscar alumno..." onkeyup="filtrarTabla('searchEntrega', 'tablaEntregas')">
            </div>
        </div>

        <!-- Tabla de Entregas -->
        <div class="card">
            <div class="table-wrap">
                <table id="tablaEntregas">
                    <thead>
                        <tr>
                            <th>Alumno</th>
                            <th>Tarea</th>
                            <th>Canal</th>
                            <th>Fecha</th>
                            <th>Archivo</th>
                            <th>Calificación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($entregas)): ?>
                            <?php foreach ($entregas as $e): ?>
                                <tr>
                                    <td><strong><?= h($e['alumno_nombre']) ?></strong></td>
                                    <td><?= h($e['tarea_titulo']) ?></td>
                                    <td><span class="badge badge-secondary"><?= h($e['canal_nombre']) ?></span></td>
                                    <td style="font-size:12px;color:#888;white-space:nowrap;"><?= fecha($e['fecha_entrega']) ?></td>
                                    <td>
                                        <?php if (!empty($e['archivo_url'])): ?>
                                            <a href="<?= BASE_URL . $e['archivo_url'] ?>" target="_blank" class="btn-sm btn-info">📎 Ver</a>
                                        <?php else: ?>
                                            <span style="color:#888;">Sin archivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($e['calificacion'] !== null): ?>
                                            <span class="badge badge-success"><?= number_format($e['calificacion'], 1) ?>/10</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">Pendiente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn-sm btn-edit" onclick="abrirModalCalificar(<?= $e['id'] ?>, '<?= h($e['alumno_nombre']) ?>', '<?= h($e['tarea_titulo']) ?>', <?= $e['calificacion'] ?? 'null' ?>, '<?= h($e['observacion_docente'] ?? '') ?>')">
                                            ✏️ Calificar
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align:center;padding:30px;color:#888;">
                                    No hay entregas en tus canales.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Calificar -->
<div class="modal-overlay" id="modalCalificar">
    <div class="modal-content" style="max-width:450px;">
        <button class="close-modal" onclick="cerrarModalCalificar()">✕</button>
        <h2>✏️ Calificar Entrega</h2>
        
        <form action="<?= BASE_URL ?>profesor/entregas.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <input type="hidden" name="accion" value="calificar">
            <input type="hidden" name="id_entrega" id="entregaId" value="0">
            
            <div class="form-group">
                <label>Alumno</label>
                <input type="text" id="alumnoNombre" class="form-control" disabled>
            </div>
            
            <div class="form-group">
                <label>Tarea</label>
                <input type="text" id="tareaTitulo" class="form-control" disabled>
            </div>
            
            <div class="form-group">
                <label>Calificación (0 - 10)</label>
                <input type="number" name="calificacion" id="calificacionInput" min="0" max="10" step="0.1" required>
            </div>
            
            <div class="form-group">
                <label>Observación (opcional)</label>
                <textarea name="observacion" id="observacionInput" rows="3" placeholder="Comentario para el alumno..."></textarea>
            </div>
            
            <button type="submit" class="btn-primary" style="width:100%;">💾 Guardar Calificación</button>
        </form>
    </div>
</div>

<script>
function abrirModalCalificar(id, alumno, tarea, calificacion, observacion) {
    document.getElementById('entregaId').value = id;
    document.getElementById('alumnoNombre').value = alumno;
    document.getElementById('tareaTitulo').value = tarea;
    document.getElementById('calificacionInput').value = calificacion !== null ? calificacion : '';
    document.getElementById('observacionInput').value = observacion || '';
    document.getElementById('modalCalificar').classList.add('activo');
}

function cerrarModalCalificar() {
    document.getElementById('modalCalificar').classList.remove('activo');
}

function aplicarFiltros() {
    const canal = document.getElementById('filterCanal').value;
    const estado = document.getElementById('filterEstado').value;
    const url = new URL(window.location.href);
    if (canal) url.searchParams.set('canal', canal);
    else url.searchParams.delete('canal');
    if (estado !== 'todos') url.searchParams.set('estado', estado);
    else url.searchParams.delete('estado');
    window.location.href = url.toString();
}

function filtrarTabla(inputId, tablaId) {
    const input = document.getElementById(inputId);
    const filter = input.value.toUpperCase();
    const table = document.getElementById(tablaId);
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        const cells = rows[i].getElementsByTagName('td');
        let found = false;
        for (let j = 0; j < cells.length; j++) {
            const text = cells[j].textContent || cells[j].innerText;
            if (text.toUpperCase().indexOf(filter) > -1) {
                found = true;
                break;
            }
        }
        rows[i].style.display = found ? '' : 'none';
    }
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') cerrarModalCalificar();
});

document.getElementById('modalCalificar').addEventListener('click', function(e) {
    if (e.target === this) cerrarModalCalificar();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>