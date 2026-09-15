<?php
// ============================================================
// ADMIN / PROFESORES.PHP — Mboa' Joaju v2.0
// Gestión de profesores (CREAR, LISTAR, ELIMINAR)
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_helper.php';

requireRole('admin');

$seccion = 'profesores';
$titulo = 'Gestión de Profesores';
$mensaje = '';
$tipo_alerta = '';

$csrf_token = $_SESSION['csrf_token'];

// ── PROCESAR CREACIÓN DE PROFESOR ──────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'crear_profesor') {
    validarCSRF();
    
    $primer_nombre = trim($_POST['primer_nombre'] ?? '');
    $primer_apellido = trim($_POST['primer_apellido'] ?? '');
    $cedula = trim($_POST['cedula'] ?? '');
    $especialidad = trim($_POST['especialidad'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';
    
    // Validaciones
    $errores = [];
    if (strlen($primer_nombre) < 2) $errores[] = 'El nombre debe tener al menos 2 caracteres.';
    if (strlen($primer_apellido) < 2) $errores[] = 'El apellido debe tener al menos 2 caracteres.';
    if (strlen($cedula) < 3) $errores[] = 'La cédula es requerida.';
    
    if (empty($errores)) {
        $resultado = crearProfesorCompleto(
            $primer_nombre,
            $primer_apellido,
            $cedula,
            $especialidad,
            $telefono,
            $contrasena
        );
        
        if ($resultado['exito']) {
            registrarLog($_SESSION['id_usuario'], 'crear_profesor', "Creó profesor: {$resultado['usuario']}");
            $mensaje = "✅ Profesor creado correctamente.<br>
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
        
        $stmt = $conn->prepare("SELECT id_usuario FROM profesores WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $profesor = $result->fetch_assoc();
        $stmt->close();
        
        if ($profesor) {
            // Verificar si tiene canales
            $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM canales WHERE id_profesor = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $has_canales = $result->fetch_assoc()['total'] > 0;
            $stmt->close();
            
            if ($has_canales) {
                $mensaje = '❌ No se puede eliminar al profesor porque tiene canales asignados.';
                $tipo_alerta = 'danger';
            } else {
                $conn->begin_transaction();
                try {
                    $stmt = $conn->prepare("DELETE FROM profesores WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $stmt->close();
                    
                    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
                    $stmt->bind_param("i", $profesor['id_usuario']);
                    $stmt->execute();
                    $stmt->close();
                    
                    $conn->commit();
                    registrarLog($_SESSION['id_usuario'], 'eliminar_profesor', 'Eliminó profesor ID: ' . $id);
                    $mensaje = '✅ Profesor eliminado correctamente.';
                    $tipo_alerta = 'success';
                } catch (Exception $e) {
                    $conn->rollback();
                    $mensaje = '❌ Error al eliminar el profesor.';
                    $tipo_alerta = 'danger';
                    error_log("Error eliminar profesor: " . $e->getMessage());
                }
            }
        }
    }
}

// ── OBTENER LISTA DE PROFESORES ───────────────────────────
$stmt = $conn->prepare(
    "SELECT p.*, u.correo, u.nombre_completo, u.activo AS usuario_activo,
            (SELECT COUNT(*) FROM canales c WHERE c.id_profesor = p.id) AS total_canales
     FROM profesores p
     INNER JOIN usuarios u ON p.id_usuario = u.id
     ORDER BY p.primer_apellido, p.primer_nombre"
);
$stmt->execute();
$profesores = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
            <button class="btn-primary" onclick="abrirModalProfesor()">➕ Crear Profesor</button>
            <div class="search-box">
                <input type="text" id="searchProfesor" placeholder="🔍 Buscar profesor..." onkeyup="filtrarTabla('searchProfesor', 'tablaProfesores')">
            </div>
        </div>

        <div class="card">
            <h3>👨‍🏫 Lista de Profesores</h3>
            <div class="table-wrap">
                <table id="tablaProfesores">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre Completo</th>
                            <th>Usuario</th>
                            <th>Cédula</th>
                            <th>Especialidad</th>
                            <th>Canales</th>
                            <th>Teléfono</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($profesores as $p): ?>
                            <tr>
                                <td><?= $p['id'] ?></td>
                                <td><strong><?= h($p['primer_nombre']) ?> <?= h($p['primer_apellido']) ?></strong></td>
                                <td><code><?= h($p['correo']) ?></code></td>
                                <td><?= h($p['cedula']) ?></td>
                                <td><?= h($p['especialidad'] ?? '-') ?></td>
                                <td><span class="badge badge-info"><?= $p['total_canales'] ?></span></td>
                                <td><?= h($p['telefono'] ?? '-') ?></td>
                                <td>
                                    <span class="badge <?= badgeEstado($p['estado']) ?>">
                                        <?= nombreEstado($p['estado']) ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn-sm btn-edit" onclick="editarProfesor(<?= $p['id'] ?>)">✏️</button>
                                    <?php if ($p['total_canales'] == 0): ?>
                                        <button class="btn-sm btn-danger" onclick="eliminarProfesor(<?= $p['id'] ?>, '<?= h($p['primer_nombre']) ?> <?= h($p['primer_apellido']) ?>')">🗑️</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL CREAR PROFESOR -->
<div class="modal-overlay" id="modalProfesor">
    <div class="modal-content" style="max-width:500px;">
        <button class="close-modal" onclick="cerrarModalProfesor()">✕</button>
        <h2>👨‍🏫 Crear Nuevo Profesor</h2>
        
        <form action="<?= BASE_URL ?>admin/profesores.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <input type="hidden" name="accion" value="crear_profesor">
            
            <div class="form-row">
                <div class="form-group">
                    <label>Nombre *</label>
                    <input type="text" name="primer_nombre" required placeholder="Ej: Carlos">
                </div>
                <div class="form-group">
                    <label>Apellido *</label>
                    <input type="text" name="primer_apellido" required placeholder="Ej: Bogado">
                </div>
            </div>
            
            <div class="form-group">
                <label>Cédula / Identificación *</label>
                <input type="text" name="cedula" required placeholder="Ej: 12345678">
            </div>
            
            <div class="form-group">
                <label>Especialidad</label>
                <input type="text" name="especialidad" placeholder="Ej: Agronomía">
            </div>
            
            <div class="form-group">
                <label>Teléfono</label>
                <input type="text" name="telefono" placeholder="Ej: 0981-234-567">
            </div>
            
            <div class="form-group">
                <label>Contraseña (opcional)</label>
                <input type="text" name="contrasena" placeholder="Dejar vacío para generar automática">
                <small class="help-text">Si se deja vacío: <strong>apellido.nombreCI</strong></small>
            </div>
            
            <button type="submit" class="btn-primary" style="width:100%;">👨‍🏫 Crear Profesor</button>
        </form>
    </div>
</div>

<script>
function abrirModalProfesor() {
    document.getElementById('modalProfesor').classList.add('activo');
}

function cerrarModalProfesor() {
    document.getElementById('modalProfesor').classList.remove('activo');
}

function eliminarProfesor(id, nombre) {
    if (confirm('¿Estás seguro de eliminar a "' + nombre + '"? Esta acción no se puede deshacer.')) {
        window.location.href = '<?= BASE_URL ?>admin/profesores.php?eliminar=' + id + '&token=<?= $csrf_token ?>';
    }
}

function editarProfesor(id) {
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
    if (e.key === 'Escape') cerrarModalProfesor();
});

document.getElementById('modalProfesor').addEventListener('click', function(e) {
    if (e.target === this) cerrarModalProfesor();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>