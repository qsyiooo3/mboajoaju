<?php
// ============================================================
// PROFESOR / ALUMNOS.PHP — Mboa' Joaju v2.0
// Gestionar alumnos en canales del profesor
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('profesor');

$seccion = 'alumnos';
$titulo = 'Alumnos en Mis Canales';
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
$stmt = $conn->prepare("SELECT id, nombre FROM canales WHERE id_profesor = ? AND activo = 1 ORDER BY nombre");
$stmt->bind_param("i", $id_profesor);
$stmt->execute();
$canales_profesor = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ── Procesar acciones ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCSRF();
    $accion = $_POST['accion'] ?? '';
    
    if ($accion === 'añadir_alumno') {
        $id_canal = (int) ($_POST['id_canal'] ?? 0);
        $id_alumno = (int) ($_POST['id_alumno'] ?? 0);
        
        // Verificar que el canal pertenece al profesor
        $stmt = $conn->prepare("SELECT id FROM canales WHERE id = ? AND id_profesor = ?");
        $stmt->bind_param("ii", $id_canal, $id_profesor);
        $stmt->execute();
        $stmt->store_result();
        $canal_valido = $stmt->num_rows > 0;
        $stmt->close();
        
        if (!$canal_valido) {
            $mensaje = 'No tienes permiso sobre este canal.';
            $tipo_alerta = 'danger';
        } else {
            // Verificar que el alumno existe
            $stmt = $conn->prepare("SELECT id FROM alumnos WHERE id = ?");
            $stmt->bind_param("i", $id_alumno);
            $stmt->execute();
            $stmt->store_result();
            $alumno_existe = $stmt->num_rows > 0;
            $stmt->close();
            
            if (!$alumno_existe) {
                $mensaje = 'El alumno no existe.';
                $tipo_alerta = 'danger';
            } else {
                // Insertar inscripción (IGNORE para evitar duplicados)
                $stmt = $conn->prepare("INSERT IGNORE INTO inscripciones (id_canal, id_alumno) VALUES (?, ?)");
                $stmt->bind_param("ii", $id_canal, $id_alumno);
                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        registrarLog($_SESSION['id_usuario'], 'añadir_alumno_canal', "Añadió alumno $id_alumno al canal $id_canal");
                        $mensaje = '✅ Alumno añadido al canal correctamente.';
                        $tipo_alerta = 'success';
                    } else {
                        $mensaje = 'El alumno ya está inscrito en este canal.';
                        $tipo_alerta = 'warning';
                    }
                } else {
                    $mensaje = 'Error al añadir el alumno.';
                    $tipo_alerta = 'danger';
                    error_log("Error añadir alumno: " . $conn->error);
                }
                $stmt->close();
            }
        }
    }
    
    if ($accion === 'eliminar_alumno') {
        $id_inscripcion = (int) ($_POST['id_inscripcion'] ?? 0);
        
        // Verificar que la inscripción pertenece a un canal del profesor
        $stmt = $conn->prepare(
            "SELECT i.id FROM inscripciones i 
             INNER JOIN canales c ON i.id_canal = c.id 
             WHERE i.id = ? AND c.id_profesor = ?"
        );
        $stmt->bind_param("ii", $id_inscripcion, $id_profesor);
        $stmt->execute();
        $stmt->store_result();
        $valido = $stmt->num_rows > 0;
        $stmt->close();
        
        if (!$valido) {
            $mensaje = 'No tienes permiso para eliminar esta inscripción.';
            $tipo_alerta = 'danger';
        } else {
            $stmt = $conn->prepare("DELETE FROM inscripciones WHERE id = ?");
            $stmt->bind_param("i", $id_inscripcion);
            if ($stmt->execute()) {
                registrarLog($_SESSION['id_usuario'], 'eliminar_alumno_canal', "Eliminó inscripción $id_inscripcion");
                $mensaje = '✅ Alumno eliminado del canal correctamente.';
                $tipo_alerta = 'success';
            } else {
                $mensaje = 'Error al eliminar el alumno.';
                $tipo_alerta = 'danger';
                error_log("Error eliminar alumno: " . $conn->error);
            }
            $stmt->close();
        }
    }
}

// ── Obtener alumnos inscritos en canales del profesor ────
$canal_filtro = isset($_GET['canal']) ? (int) $_GET['canal'] : 0;

$sql = "SELECT i.id AS inscripcion_id, i.fecha_insc,
        a.id AS alumno_id, a.primer_nombre, a.primer_apellido, a.cedula, a.telefono_alumno,
        u.nombre_completo,
        c.id AS canal_id, c.nombre AS canal_nombre
        FROM inscripciones i 
        INNER JOIN alumnos a ON i.id_alumno = a.id 
        INNER JOIN usuarios u ON a.id_usuario = u.id 
        INNER JOIN canales c ON i.id_canal = c.id 
        WHERE c.id_profesor = ?";
$params = [$id_profesor];
$types = "i";

if ($canal_filtro > 0) {
    $sql .= " AND c.id = ?";
    $params[] = $canal_filtro;
    $types .= "i";
}

$sql .= " ORDER BY c.nombre, a.primer_apellido, a.primer_nombre";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$alumnos_inscritos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ── Obtener todos los alumnos activos (para añadir) ──────
$stmt = $conn->prepare(
    "SELECT a.id, a.primer_nombre, a.primer_apellido, a.cedula, u.nombre_completo 
     FROM alumnos a 
     INNER JOIN usuarios u ON a.id_usuario = u.id 
     WHERE a.estado = 'activo' AND u.activo = 1 
     ORDER BY a.primer_apellido, a.primer_nombre"
);
$stmt->execute();
$todos_alumnos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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

        <!-- Filtro y acciones -->
        <div class="toolbar">
            <div class="filter-group">
                <select id="filterCanal" onchange="filtrarPorCanal()">
                    <option value="">Todos los canales</option>
                    <?php foreach ($canales_profesor as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($canal_filtro == $c['id']) ? 'selected' : '' ?>>
                            <?= h($c['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="btn-primary" onclick="abrirModalAñadir()">➕ Añadir Alumno</button>
        </div>

        <!-- Lista de Alumnos -->
        <div class="card">
            <h3>👥 Alumnos en Mis Canales</h3>
            <?php if (!empty($alumnos_inscritos)): ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Alumno</th>
                                <th>Cédula</th>
                                <th>Canal</th>
                                <th>Teléfono</th>
                                <th>Fecha Inscripción</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alumnos_inscritos as $a): ?>
                                <tr>
                                    <td><strong><?= h($a['nombre_completo']) ?></strong></td>
                                    <td><?= h($a['cedula']) ?></td>
                                    <td><span class="badge badge-secondary"><?= h($a['canal_nombre']) ?></span></td>
                                    <td><?= h($a['telefono_alumno'] ?? '-') ?></td>
                                    <td style="font-size:12px;color:#888;"><?= fecha($a['fecha_insc']) ?></td>
                                    <td>
                                        <form action="<?= BASE_URL ?>profesor/alumnos.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                            <input type="hidden" name="accion" value="eliminar_alumno">
                                            <input type="hidden" name="id_inscripcion" value="<?= $a['inscripcion_id'] ?>">
                                            <button type="submit" class="btn-sm btn-danger" onclick="return confirm('¿Eliminar a <?= h($a['nombre_completo']) ?> de <?= h($a['canal_nombre']) ?>?')">
                                                🗑️
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="color:#888;text-align:center;padding:30px 0;">
                    No hay alumnos inscritos en tus canales.
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Añadir Alumno -->
<div class="modal-overlay" id="modalAñadir">
    <div class="modal-content" style="max-width:500px;">
        <button class="close-modal" onclick="cerrarModalAñadir()">✕</button>
        <h2>➕ Añadir Alumno a Canal</h2>
        
        <form action="<?= BASE_URL ?>profesor/alumnos.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <input type="hidden" name="accion" value="añadir_alumno">
            
            <div class="form-group">
                <label>Canal *</label>
                <select name="id_canal" required>
                    <option value="">Seleccionar canal...</option>
                    <?php foreach ($canales_profesor as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($canal_filtro == $c['id']) ? 'selected' : '' ?>>
                            <?= h($c['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($canales_profesor)): ?>
                    <small style="color:var(--color-error);">⚠️ No tienes canales. Crea uno primero.</small>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label>Alumno *</label>
                <select name="id_alumno" required>
                    <option value="">Seleccionar alumno...</option>
                    <?php foreach ($todos_alumnos as $a): ?>
                        <option value="<?= $a['id'] ?>"><?= h($a['nombre_completo']) ?> (<?= h($a['cedula']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" class="btn-primary" style="width:100%;">➕ Añadir Alumno</button>
        </form>
    </div>
</div>

<script>
function abrirModalAñadir() {
    document.getElementById('modalAñadir').classList.add('activo');
}

function cerrarModalAñadir() {
    document.getElementById('modalAñadir').classList.remove('activo');
}

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
    if (e.key === 'Escape') cerrarModalAñadir();
});

document.getElementById('modalAñadir').addEventListener('click', function(e) {
    if (e.target === this) cerrarModalAñadir();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>