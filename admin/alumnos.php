<?php
// ============================================================
// ADMIN / ALUMNOS.PHP — Mboa' Joaju v2.0
// Gestión de alumnos (CREAR, LISTAR, ELIMINAR)
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_helper.php';

requireRole('admin');

$seccion = 'alumnos';
$titulo = 'Gestión de Alumnos';
$mensaje = '';
$tipo_alerta = '';

$csrf_token = $_SESSION['csrf_token'];

// ── PROCESAR CREACIÓN DE ALUMNO ───────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'crear_alumno') {
    validarCSRF();
    
    $primer_nombre = trim($_POST['primer_nombre'] ?? '');
    $primer_apellido = trim($_POST['primer_apellido'] ?? '');
    $cedula = trim($_POST['cedula'] ?? '');
    $curso = trim($_POST['curso'] ?? '');
    $seccion_alumno = trim($_POST['seccion'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $telefono_padre = trim($_POST['telefono_padre'] ?? '');
    $nombre_padre = trim($_POST['nombre_padre'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';
    
    // Validaciones
    $errores = [];
    if (strlen($primer_nombre) < 2) $errores[] = 'El nombre debe tener al menos 2 caracteres.';
    if (strlen($primer_apellido) < 2) $errores[] = 'El apellido debe tener al menos 2 caracteres.';
    if (strlen($cedula) < 3) $errores[] = 'La cédula es requerida.';
    
    if (empty($errores)) {
        // Usar la función helper para crear alumno completo
        $resultado = crearAlumnoCompleto(
            $primer_nombre,
            $primer_apellido,
            $cedula,
            $curso,
            $seccion_alumno,
            $telefono,
            $telefono_padre,
            $nombre_padre,
            $contrasena
        );
        
        if ($resultado['exito']) {
            registrarLog($_SESSION['id_usuario'], 'crear_alumno', "Creó alumno: {$resultado['usuario']}");
            $mensaje = "✅ Alumno creado correctamente.<br>
                        Usuario: <strong>{$resultado['usuario']}</strong><br>
                        Contraseña: <strong>{$resultado['contrasena']}</strong>";
            $tipo_alerta = 'success';
        } else {
            $mensaje = '❌ ' . $resultado['mensaje'];
            $tipo_alerta = 'danger';
        }
    } else {
        $mensaje = '❌ ' . implode(' ', $errores);
        $tipo_alerta = 'danger';
    }
}

// ── PROCESAR ELIMINACIÓN ──────────────────────────────────
if (isset($_GET['eliminar']) && isset($_GET['token'])) {
    if (hash_equals($csrf_token, $_GET['token'])) {
        $id = (int) $_GET['eliminar'];
        
        $stmt = $conn->prepare("SELECT id_usuario FROM alumnos WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $alumno = $result->fetch_assoc();
        $stmt->close();
        
        if ($alumno) {
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("DELETE FROM alumnos WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
                
                $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
                $stmt->bind_param("i", $alumno['id_usuario']);
                $stmt->execute();
                $stmt->close();
                
                $conn->commit();
                registrarLog($_SESSION['id_usuario'], 'eliminar_alumno', 'Eliminó alumno ID: ' . $id);
                $mensaje = '✅ Alumno eliminado correctamente.';
                $tipo_alerta = 'success';
            } catch (Exception $e) {
                $conn->rollback();
                $mensaje = '❌ Error al eliminar el alumno.';
                $tipo_alerta = 'danger';
                error_log("Error eliminar alumno: " . $e->getMessage());
            }
        }
    } else {
        $mensaje = '❌ Token de seguridad inválido.';
        $tipo_alerta = 'danger';
    }
}

// ── OBTENER LISTA DE ALUMNOS ──────────────────────────────
$stmt = $conn->prepare(
    "SELECT a.*, u.correo, u.nombre_completo, u.activo AS usuario_activo
     FROM alumnos a
     INNER JOIN usuarios u ON a.id_usuario = u.id
     ORDER BY a.primer_apellido, a.primer_nombre"
);
$stmt->execute();
$alumnos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

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

        <div class="toolbar">
            <button class="btn-primary" onclick="abrirModalAlumno()">➕ Crear Alumno</button>
            <div class="search-box">
                <input type="text" id="searchAlumno" placeholder="🔍 Buscar alumno..." onkeyup="filtrarTabla('searchAlumno', 'tablaAlumnos')">
            </div>
        </div>

        <div class="card">
            <h3>👥 Lista de Alumnos</h3>
            <div class="table-wrap">
                <table id="tablaAlumnos">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre Completo</th>
                            <th>Usuario</th>
                            <th>Cédula</th>
                            <th>Curso</th>
                            <th>Teléfono</th>
                            <th>Padre/Tutor</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alumnos as $a): ?>
                            <tr>
                                <td><?= $a['id'] ?></td>
                                <td><strong><?= h($a['primer_nombre']) ?> <?= h($a['primer_apellido']) ?></strong></td>
                                <td><code><?= h($a['correo']) ?></code></td>
                                <td><?= h($a['cedula']) ?></td>
                                <td><?= h($a['curso'] ?? '') ?> - <?= h($a['seccion'] ?? '') ?></td>
                                <td><?= h($a['telefono_alumno'] ?? '') ?></td>
                                <td><?= h($a['nombre_padre'] ?? '') ?></td>
                                <td>
                                    <span class="badge <?= badgeEstado($a['estado']) ?>">
                                        <?= nombreEstado($a['estado']) ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn-sm btn-edit" onclick="editarAlumno(<?= $a['id'] ?>)">✏️</button>
                                    <button class="btn-sm btn-danger" onclick="eliminarAlumno(<?= $a['id'] ?>, '<?= h($a['primer_nombre']) ?> <?= h($a['primer_apellido']) ?>')">🗑️</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL CREAR ALUMNO -->
<div class="modal-overlay" id="modalAlumno">
    <div class="modal-content" style="max-width:550px;">
        <button class="close-modal" onclick="cerrarModalAlumno()">✕</button>
        <h2>👤 Crear Nuevo Alumno</h2>
        
        <form action="<?= BASE_URL ?>admin/alumnos.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <input type="hidden" name="accion" value="crear_alumno">
            
            <div class="form-row">
                <div class="form-group">
                    <label>Nombre *</label>
                    <input type="text" name="primer_nombre" required placeholder="Ej: María">
                </div>
                <div class="form-group">
                    <label>Apellido *</label>
                    <input type="text" name="primer_apellido" required placeholder="Ej: Benítez">
                </div>
            </div>
            
            <div class="form-group">
                <label>Cédula / Identificación *</label>
                <input type="text" name="cedula" required placeholder="Ej: 12345678">
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
            
            <div class="form-group">
                <label>Teléfono del Alumno</label>
                <input type="text" name="telefono" placeholder="Ej: 0981-234-567">
            </div>
            
            <div class="form-group">
                <label>Nombre del Padre/Tutor</label>
                <input type="text" name="nombre_padre" placeholder="Ej: Roberto Benítez">
            </div>
            
            <div class="form-group">
                <label>Teléfono del Padre/Tutor</label>
                <input type="text" name="telefono_padre" placeholder="Ej: 0982-345-678">
            </div>
            
            <div class="form-group">
                <label>Contraseña (opcional)</label>
                <input type="text" name="contrasena" placeholder="Dejar vacío para generar automática">
                <small class="help-text">Si se deja vacío: <strong>apellido.nombreCI</strong></small>
            </div>
            
            <button type="submit" class="btn-primary" style="width:100%;">👤 Crear Alumno</button>
        </form>
    </div>
</div>

<script>
function abrirModalAlumno() {
    document.getElementById('modalAlumno').classList.add('activo');
}

function cerrarModalAlumno() {
    document.getElementById('modalAlumno').classList.remove('activo');
}

function eliminarAlumno(id, nombre) {
    if (confirm('¿Estás seguro de eliminar a "' + nombre + '"? Esta acción no se puede deshacer.')) {
        window.location.href = '<?= BASE_URL ?>admin/alumnos.php?eliminar=' + id + '&token=<?= $csrf_token ?>';
    }
}

function editarAlumno(id) {
    window.location.href = '<?= BASE_URL ?>admin/usuarios.php?edit=' + id;
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
    if (e.key === 'Escape') cerrarModalAlumno();
});

document.getElementById('modalAlumno').addEventListener('click', function(e) {
    if (e.target === this) cerrarModalAlumno();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>