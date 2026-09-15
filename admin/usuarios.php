<?php
// ============================================================
// ADMIN / USUARIOS.PHP — Mboa' Joaju v2.0
// CRUD de usuarios del sistema
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_helper.php';

requireRole('admin');

$seccion = 'usuarios';
$titulo = 'Gestión de Usuarios';
$mensaje = '';
$tipo_alerta = '';

$csrf_token = $_SESSION['csrf_token'];

// ── Obtener lista de usuarios ─────────────────────────────
$stmt = $conn->prepare(
    "SELECT u.id, u.correo, u.nombre_completo, u.rol, u.fecha_registro, u.activo,
            a.curso, a.seccion,
            p.especialidad
     FROM usuarios u
     LEFT JOIN alumnos a ON u.id = a.id_usuario
     LEFT JOIN profesores p ON u.id = p.id_usuario
     WHERE u.id != 1
     ORDER BY u.rol, u.nombre_completo"
);
$stmt->execute();
$usuarios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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

        <!-- ✅ BOTÓN CREAR - BIEN UBICADO (FUERA DEL FOREACH) -->
        <div class="toolbar">
            <button class="btn-primary" onclick="abrirModal()">➕ Crear Nuevo Usuario</button>
            <div class="search-box">
                <input type="text" id="searchUsuario" placeholder="🔍 Buscar usuario..." onkeyup="filtrarTabla('searchUsuario', 'tablaUsuarios')">
            </div>
        </div>

        <!-- ✅ TABLA - ESTRUCTURA CORRECTA -->
        <div class="card">
            <div class="table-wrap">
                <table id="tablaUsuarios">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>Detalle</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td><?= $u['id'] ?></td>
                                <td><strong><?= h($u['nombre_completo']) ?></strong></td>
                                <td><code><?= h($u['correo']) ?></code></td>
                                <td><span class="badge <?= badgeRol($u['rol']) ?>"><?= nombreRol($u['rol']) ?></span></td>
                                <td>
                                    <?php if ($u['rol'] === 'alumno'): ?>
                                        <?= h($u['curso'] ?? '') ?> - <?= h($u['seccion'] ?? '') ?>
                                    <?php elseif ($u['rol'] === 'profesor'): ?>
                                        <?= h($u['especialidad'] ?? '') ?>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $u['activo'] ? 'badge-success' : 'badge-danger' ?>">
                                        <?= $u['activo'] ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn-sm btn-edit" onclick="editarUsuario(<?= $u['id'] ?>)">✏️</button>
                                    <?php if ($u['rol'] !== 'admin'): ?>
                                        <button class="btn-sm btn-danger" onclick="eliminarUsuario(<?= $u['id'] ?>, '<?= h($u['nombre_completo']) ?>')">🗑️</button>
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

<!-- Modal Crear/Editar Usuario -->
<div class="modal-overlay" id="modalUsuario">
    <div class="modal-content">
        <button class="close-modal" onclick="cerrarModal()">✕</button>
        <h2 id="modalTitle">Crear Usuario</h2>
        
        <form action="<?= BASE_URL ?>admin/usuarios_guardar.php" method="POST" id="formUsuario">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <input type="hidden" name="id" id="userId" value="0">
            
            <div class="form-group">
                <label>Rol</label>
                <select name="rol" id="rol" required onchange="cambiarRol()">
                    <option value="">Seleccionar...</option>
                    <option value="alumno">Alumno</option>
                    <option value="profesor">Profesor</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Nombre Completo</label>
                <input type="text" name="nombre_completo" id="nombre_completo" required placeholder="Ej: María Benítez">
            </div>
            
            <div class="form-group">
                <label>Usuario (Correo)</label>
                <input type="text" name="correo" id="correo" required placeholder="Ej: benitez.maria12345">
                <small class="help-text">Formato sugerido: apellido.nombreCI</small>
            </div>
            
            <div class="form-group" id="grupo_cedula">
                <label>Cédula / Identificación</label>
                <input type="text" name="cedula" id="cedula" placeholder="Ej: 12345678">
            </div>
            
            <div class="form-group" id="grupo_curso" style="display:none;">
                <label>Curso</label>
                <select name="curso" id="curso">
                    <option value="">Seleccionar...</option>
                    <option value="1ro">1ro</option>
                    <option value="2do">2do</option>
                    <option value="3ro">3ro</option>
                </select>
            </div>
            
            <div class="form-group" id="grupo_seccion" style="display:none;">
                <label>Sección</label>
                <select name="seccion" id="seccion">
                    <option value="">Seleccionar...</option>
                    <option value="A">A</option>
                    <option value="B">B</option>
                    <option value="C">C</option>
                </select>
            </div>
            
            <div class="form-group" id="grupo_especialidad" style="display:none;">
                <label>Especialidad</label>
                <input type="text" name="especialidad" id="especialidad" placeholder="Ej: Agronomía">
            </div>
            
            <div class="form-group" id="grupo_telefono" style="display:none;">
                <label>Teléfono</label>
                <input type="text" name="telefono" id="telefono" placeholder="Ej: 0981-234-567">
            </div>
            
            <div class="form-group" id="grupo_telefono_padre" style="display:none;">
                <label>Teléfono del Padre/Tutor</label>
                <input type="text" name="telefono_padre" id="telefono_padre" placeholder="Ej: 0982-345-678">
            </div>
            
            <div class="form-group" id="grupo_nombre_padre" style="display:none;">
                <label>Nombre del Padre/Tutor</label>
                <input type="text" name="nombre_padre" id="nombre_padre" placeholder="Ej: Roberto Benítez">
            </div>
            
            <div class="form-group">
                <label>Contraseña</label>
                <input type="password" name="contrasena" id="contrasena" placeholder="Dejar vacío para generar automática">
                <small class="help-text">Si se deja vacío, se generará automáticamente: apellido.nombreCI</small>
            </div>
            
            <button type="submit" class="btn-primary" style="width:100%;">Guardar Usuario</button>
        </form>
    </div>
</div>

<script>
function abrirModal() {
    document.getElementById('modalTitle').innerText = 'Crear Nuevo Usuario';
    document.getElementById('userId').value = '0';
    document.getElementById('formUsuario').reset();
    document.getElementById('contrasena').value = '';
    document.getElementById('modalUsuario').classList.add('activo');
    cambiarRol();
}

function cerrarModal() {
    document.getElementById('modalUsuario').classList.remove('activo');
}

function editarUsuario(id) {
    // Cargar datos via AJAX
    fetch('<?= BASE_URL ?>admin/usuarios_obtener.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.exito) {
                document.getElementById('modalTitle').innerText = 'Editar Usuario';
                document.getElementById('userId').value = data.id;
                document.getElementById('rol').value = data.rol;
                document.getElementById('nombre_completo').value = data.nombre_completo;
                document.getElementById('correo').value = data.correo;
                document.getElementById('contrasena').value = '';
                document.getElementById('cedula').value = data.cedula || '';
                document.getElementById('curso').value = data.curso || '';
                document.getElementById('seccion').value = data.seccion || '';
                document.getElementById('especialidad').value = data.especialidad || '';
                document.getElementById('telefono').value = data.telefono || '';
                document.getElementById('telefono_padre').value = data.telefono_padre || '';
                document.getElementById('nombre_padre').value = data.nombre_padre || '';
                cambiarRol();
                document.getElementById('modalUsuario').classList.add('activo');
            } else {
                alert('Error al cargar los datos del usuario.');
            }
        })
        .catch(error => {
            alert('Error de conexión.');
        });
}

function eliminarUsuario(id, nombre) {
    if (confirm('¿Estás seguro de eliminar a "' + nombre + '"? Esta acción no se puede deshacer.')) {
        window.location.href = '<?= BASE_URL ?>admin/usuarios_eliminar.php?id=' + id + '&token=<?= $csrf_token ?>';
    }
}

function cambiarRol() {
    const rol = document.getElementById('rol').value;
    // Mostrar/ocultar campos según rol
    document.getElementById('campos_alumno').style.display = (rol === 'alumno') ? 'block' : 'none';
    document.getElementById('campos_profesor').style.display = (rol === 'profesor') ? 'block' : 'none';
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

// Cerrar modal con Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') cerrarModal();
});

// Cerrar modal al hacer clic fuera
document.getElementById('modalUsuario').addEventListener('click', function(e) {
    if (e.target === this) cerrarModal();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>