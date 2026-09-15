<?php
// ============================================================
// ADMIN / CANALES.PHP — Mboa' Joaju v2.0
// Gestión completa de canales (crear, editar, activar, eliminar)
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

$seccion = 'canales';
$titulo = 'Gestión de Canales';

// Recuperar mensajes de sesión
$mensaje = $_SESSION['error_mensaje'] ?? '';
$tipo_alerta = $_SESSION['error_tipo'] ?? '';
unset($_SESSION['error_mensaje'], $_SESSION['error_tipo']);

$csrf_token = $_SESSION['csrf_token'];

// ── Procesar eliminación ──────────────────────────────────
if (isset($_GET['eliminar']) && isset($_GET['token'])) {
    if (hash_equals($csrf_token, $_GET['token'])) {
        $id = (int) $_GET['eliminar'];

        $stmt = $conn->prepare("SELECT nombre FROM canales WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $canal = $result->fetch_assoc();
        $stmt->close();

        if ($canal) {
            $stmt = $conn->prepare("DELETE FROM canales WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                registrarLog($_SESSION['id_usuario'], 'eliminar_canal_admin', 'Eliminó canal: ' . $canal['nombre']);
                $mensaje = '✅ Canal "' . h($canal['nombre']) . '" eliminado correctamente.';
                $tipo_alerta = 'success';
            } else {
                $mensaje = '❌ Error al eliminar el canal.';
                $tipo_alerta = 'danger';
                error_log("Error eliminar canal: " . $conn->error);
            }
            $stmt->close();
        }
    } else {
        $mensaje = '❌ Token de seguridad inválido.';
        $tipo_alerta = 'danger';
    }
}

// ── Procesar cambio de estado ─────────────────────────────
if (isset($_GET['estado']) && isset($_GET['id']) && isset($_GET['token'])) {
    if (hash_equals($csrf_token, $_GET['token'])) {
        $id = (int) $_GET['id'];
        $nuevo_estado = (int) $_GET['estado'];

        $stmt = $conn->prepare("UPDATE canales SET activo = ? WHERE id = ?");
        $stmt->bind_param("ii", $nuevo_estado, $id);
        if ($stmt->execute()) {
            registrarLog($_SESSION['id_usuario'], 'cambiar_estado_canal', "Cambió estado canal ID: $id");
            $mensaje = '✅ Estado del canal actualizado.';
            $tipo_alerta = 'success';
        }
        $stmt->close();
    }
}

// ── Obtener TODOS los canales ─────────────────────────────
$stmt = $conn->prepare(
    "SELECT c.*, 
            u.nombre_completo AS profesor_nombre,
            p.id AS profesor_id,
            (SELECT COUNT(*) FROM inscripciones i WHERE i.id_canal = c.id) AS total_alumnos,
            (SELECT COUNT(*) FROM publicaciones p2 WHERE p2.id_canal = c.id) AS total_publicaciones
     FROM canales c
     INNER JOIN profesores p ON c.id_profesor = p.id
     INNER JOIN usuarios u ON p.id_usuario = u.id
     ORDER BY c.fecha_creacion DESC"
);
$stmt->execute();
$canales = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ── Obtener lista de profesores activos (para asignar) ────
$stmt = $conn->prepare(
    "SELECT p.id, u.nombre_completo, p.especialidad 
     FROM profesores p 
     INNER JOIN usuarios u ON p.id_usuario = u.id 
     WHERE p.estado = 'activo' AND u.activo = 1 
     ORDER BY u.nombre_completo"
);
$stmt->execute();
$profesores_activos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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

        <!-- Estadísticas rápidas -->
        <div class="stats-grid mini-stats">
            <div class="stat-card stat-primary">
                <div class="stat-icon">📺</div>
                <div class="stat-info">
                    <span class="stat-value"><?= count($canales) ?></span>
                    <span class="stat-label">Total Canales</span>
                </div>
            </div>
            <div class="stat-card stat-success">
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <span class="stat-value"><?= count(array_filter($canales, fn($c) => $c['activo'] == 1)) ?></span>
                    <span class="stat-label">Activos</span>
                </div>
            </div>
            <div class="stat-card stat-danger">
                <div class="stat-icon">❌</div>
                <div class="stat-info">
                    <span class="stat-value"><?= count(array_filter($canales, fn($c) => $c['activo'] == 0)) ?></span>
                    <span class="stat-label">Inactivos</span>
                </div>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="toolbar">
            <button class="btn-primary" onclick="abrirModalCanal()">➕ Crear Canal</button>
            <div class="search-box">
                <input type="text" id="searchCanal" placeholder="🔍 Buscar canal..." onkeyup="filtrarTabla('searchCanal', 'tablaCanales')">
            </div>
        </div>

        <!-- Tabla de Canales -->
        <div class="card">
            <div class="table-wrap">
                <table id="tablaCanales">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Profesor</th>
                            <th>Curso</th>
                            <th>Alumnos</th>
                            <th>Pubs.</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($canales)): ?>
                            <?php foreach ($canales as $c): ?>
                                <tr>
                                    <td><?= $c['id'] ?></td>
                                    <td>
                                        <strong><?= h($c['nombre']) ?></strong>
                                        <?php if (!empty($c['descripcion'])): ?>
                                            <br><small style="color:#888;"><?= h(truncar($c['descripcion'], 50)) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= h($c['profesor_nombre']) ?></td>
                                    <td><?= h($c['curso'] ?? '-') ?> <?= h($c['seccion'] ?? '') ?></td>
                                    <td><span class="badge badge-info"><?= $c['total_alumnos'] ?></span></td>
                                    <td><span class="badge badge-primary"><?= $c['total_publicaciones'] ?></span></td>
                                    <td>
                                        <span class="badge <?= $c['activo'] ? 'badge-success' : 'badge-danger' ?>">
                                            <?= $c['activo'] ? 'Activo' : 'Inactivo' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn-sm btn-edit" 
                                                onclick='editarCanal(<?= json_encode([
                                                    "id" => $c["id"],
                                                    "nombre" => $c["nombre"],
                                                    "descripcion" => $c["descripcion"] ?? "",
                                                    "id_profesor" => $c["profesor_id"],
                                                    "curso" => $c["curso"] ?? "",
                                                    "seccion" => $c["seccion"] ?? "",
                                                    "color" => $c["color"] ?? "#1a4a2a",
                                                    "icono" => $c["icono"] ?? "📚"
                                                ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                            ✏️
                                        </button>
                                        <?php if ($c['activo']): ?>
                                            <a href="<?= BASE_URL ?>admin/canales.php?estado=0&id=<?= $c['id'] ?>&token=<?= urlencode($csrf_token) ?>" 
                                               class="btn-sm btn-warning" 
                                               onclick="return confirm('¿Desactivar este canal?')">⛔</a>
                                        <?php else: ?>
                                            <a href="<?= BASE_URL ?>admin/canales.php?estado=1&id=<?= $c['id'] ?>&token=<?= urlencode($csrf_token) ?>" 
                                               class="btn-sm btn-success" 
                                               onclick="return confirm('¿Activar este canal?')">✅</a>
                                        <?php endif; ?>
                                        <a href="<?= BASE_URL ?>admin/canales.php?eliminar=<?= $c['id'] ?>&token=<?= urlencode($csrf_token) ?>" 
                                           class="btn-sm btn-danger" 
                                           onclick="return confirm('⚠️ ¿Eliminar el canal «<?= h($c['nombre']) ?>»? Se eliminarán publicaciones, entregas e inscripciones asociadas.')">🗑️</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align:center;padding:30px;color:#888;">
                                    No hay canales registrados en el sistema.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     MODAL CREAR / EDITAR CANAL
     ══════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="modalCanal">
    <div class="modal-content" style="max-width:520px;">
        <button class="close-modal" onclick="cerrarModalCanal()">✕</button>
        <h2 id="modalCanalTitle">📺 Crear Nuevo Canal</h2>

        <form action="<?= BASE_URL ?>admin/canales_guardar.php" 
              method="POST" 
              data-loader="Guardando canal...">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <input type="hidden" name="id" id="canalId" value="0">

            <div class="form-group">
                <label>Nombre del Canal *</label>
                <input type="text" name="nombre" id="canalNombre" required 
                       placeholder="Ej: Agronomía I" maxlength="100">
            </div>

            <div class="form-group">
                <label>Profesor Responsable *</label>
                <select name="id_profesor" id="canalProfesor" required>
                    <option value="">Seleccionar profesor...</option>
                    <?php foreach ($profesores_activos as $p): ?>
                        <option value="<?= $p['id'] ?>">
                            <?= h($p['nombre_completo']) ?>
                            <?= $p['especialidad'] ? ' (' . h($p['especialidad']) . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($profesores_activos)): ?>
                    <small style="color:var(--color-error);">
                        ⚠️ No hay profesores activos. Crea uno primero en "Profesores".
                    </small>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Descripción</label>
                <textarea name="descripcion" id="canalDescripcion" rows="3" 
                          maxlength="500" placeholder="Descripción del canal..."></textarea>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Curso</label>
                    <select name="curso" id="canalCurso">
                        <option value="">Sin curso</option>
                        <option value="1ro">1ro</option>
                        <option value="2do">2do</option>
                        <option value="3ro">3ro</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Sección</label>
                    <select name="seccion" id="canalSeccion">
                        <option value="">Sin sección</option>
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                    </select>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Color</label>
                    <input type="color" name="color" id="canalColor" value="#1a4a2a" 
                           style="height:42px;cursor:pointer;">
                </div>
                <div class="form-group">
                    <label>Icono</label>
                    <select name="icono" id="canalIcono">
                        <option value="📚">📚 Libros</option>
                        <option value="🌾">🌾 Agricultura</option>
                        <option value="🐄">🐄 Ganadería</option>
                        <option value="🔬">🔬 Ciencias</option>
                        <option value="📐">📐 Matemáticas</option>
                        <option value="📝">📝 Escritura</option>
                        <option value="🎨">🎨 Artes</option>
                        <option value="💻">💻 Tecnología</option>
                        <option value="🌍">🌍 Geografía</option>
                        <option value="🧪">🧪 Química</option>
                        <option value="⚡">⚡ Física</option>
                        <option value="🎵">🎵 Música</option>
                        <option value="🏃">🏃 Deportes</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn-form" id="btnCanalSubmit">
                📺 Crear Canal
            </button>
        </form>
    </div>
</div>

<script>
function abrirModalCanal() {
    document.getElementById('modalCanalTitle').innerText = '📺 Crear Nuevo Canal';
    document.getElementById('canalId').value = '0';
    document.getElementById('canalNombre').value = '';
    document.getElementById('canalProfesor').value = '';
    document.getElementById('canalDescripcion').value = '';
    document.getElementById('canalCurso').value = '';
    document.getElementById('canalSeccion').value = '';
    document.getElementById('canalColor').value = '#1a4a2a';
    document.getElementById('canalIcono').value = '📚';
    document.getElementById('btnCanalSubmit').innerText = '📺 Crear Canal';
    document.getElementById('modalCanal').classList.add('activo');
    document.getElementById('canalNombre').focus();
}

function editarCanal(data) {
    document.getElementById('modalCanalTitle').innerText = '✏️ Editar Canal';
    document.getElementById('canalId').value = data.id;
    document.getElementById('canalNombre').value = data.nombre;
    document.getElementById('canalProfesor').value = data.id_profesor;
    document.getElementById('canalDescripcion').value = data.descripcion;
    document.getElementById('canalCurso').value = data.curso;
    document.getElementById('canalSeccion').value = data.seccion;
    document.getElementById('canalColor').value = data.color;
    document.getElementById('canalIcono').value = data.icono;
    document.getElementById('btnCanalSubmit').innerText = '💾 Guardar Cambios';
    document.getElementById('modalCanal').classList.add('activo');
    document.getElementById('canalNombre').focus();
}

function cerrarModalCanal() {
    document.getElementById('modalCanal').classList.remove('activo');
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

// Cerrar con Escape
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') cerrarModalCanal();
});

// Cerrar al hacer clic fuera
document.getElementById('modalCanal').addEventListener('click', function (e) {
    if (e.target === this) cerrarModalCanal();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>