<?php
// ============================================================
// ALUMNO / PUBLICACIONES.PHP — Mboa' Joaju v2.0
// Ver publicaciones de los canales donde está inscrito
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('alumno');

$seccion = 'publicaciones';
$titulo = 'Publicaciones';

$id_usuario = (int) $_SESSION['id_usuario'];

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

// ── Obtener canales del alumno para filtro ────────────────
$stmt = $conn->prepare(
    "SELECT c.id, c.nombre 
     FROM canales c 
     INNER JOIN inscripciones i ON i.id_canal = c.id 
     WHERE i.id_alumno = ? AND c.activo = 1 
     ORDER BY c.nombre"
);
$stmt->bind_param("i", $id_alumno);
$stmt->execute();
$canales_alumno = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ── Filtros ─────────────────────────────────────────────────
$canal_filtro = isset($_GET['canal']) ? (int) $_GET['canal'] : 0;
$tipo_filtro = $_GET['tipo'] ?? 'todos';

// ── Obtener publicaciones ──────────────────────────────────
$sql = "SELECT p.*, c.nombre AS canal_nombre, 
               u.nombre_completo AS profesor_nombre,
               (SELECT COUNT(*) FROM entregas e 
                WHERE e.id_publicacion = p.id AND e.id_alumno = ?) AS ya_entregue
        FROM publicaciones p 
        INNER JOIN canales c ON p.id_canal = c.id 
        INNER JOIN inscripciones i ON i.id_canal = c.id 
        INNER JOIN profesores pr ON p.id_profesor = pr.id
        INNER JOIN usuarios u ON pr.id_usuario = u.id
        WHERE i.id_alumno = ?";
$params = [$id_alumno, $id_alumno];
$types = "ii";

if ($canal_filtro > 0) {
    $sql .= " AND c.id = ?";
    $params[] = $canal_filtro;
    $types .= "i";
}

if ($tipo_filtro === 'tarea') {
    $sql .= " AND p.tipo_publicacion = 'tarea'";
} elseif ($tipo_filtro === 'anuncio') {
    $sql .= " AND p.tipo_publicacion = 'anuncio'";
} elseif ($tipo_filtro === 'material') {
    $sql .= " AND p.tipo_publicacion = 'material'";
}

$sql .= " ORDER BY p.fecha_creacion DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$publicaciones = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
        <!-- Filtros -->
        <div class="toolbar">
            <div class="filter-group">
                <select id="filterCanal" onchange="aplicarFiltros()">
                    <option value="">Todos los canales</option>
                    <?php foreach ($canales_alumno as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($canal_filtro == $c['id']) ? 'selected' : '' ?>>
                            <?= h($c['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select id="filterTipo" onchange="aplicarFiltros()">
                    <option value="todos" <?= $tipo_filtro === 'todos' ? 'selected' : '' ?>>Todos</option>
                    <option value="anuncio" <?= $tipo_filtro === 'anuncio' ? 'selected' : '' ?>>📢 Anuncios</option>
                    <option value="material" <?= $tipo_filtro === 'material' ? 'selected' : '' ?>>📚 Material</option>
                    <option value="tarea" <?= $tipo_filtro === 'tarea' ? 'selected' : '' ?>>📝 Tareas</option>
                </select>
            </div>
            <div class="search-box">
                <input type="text" id="searchPub" placeholder="🔍 Buscar..." onkeyup="filtrarPublicaciones()">
            </div>
        </div>

        <!-- Lista de Publicaciones -->
        <div class="card">
            <h3>📝 Publicaciones</h3>
            <?php if (!empty($publicaciones)): ?>
                <?php foreach ($publicaciones as $pub): ?>
                    <div class="publicacion-item" data-titulo="<?= h($pub['titulo']) ?>" data-contenido="<?= h($pub['contenido']) ?>">
                        <div class="pub-header">
                            <div class="pub-titulo">
                                <span class="badge <?= $pub['tipo_publicacion'] === 'tarea' ? 'badge-warning' : ($pub['tipo_publicacion'] === 'anuncio' ? 'badge-info' : 'badge-secondary') ?>">
                                    <?= ucfirst($pub['tipo_publicacion']) ?>
                                </span>
                                <strong><?= h($pub['titulo']) ?></strong>
                            </div>
                            <span class="pub-fecha"><?= fecha($pub['fecha_creacion']) ?></span>
                        </div>
                        <div class="pub-body">
                            <div class="pub-meta">
                                <span class="badge badge-secondary">📺 <?= h($pub['canal_nombre']) ?></span>
                                <span class="badge badge-secondary">👨‍🏫 <?= h($pub['profesor_nombre']) ?></span>
                                <?php if ($pub['tipo_publicacion'] === 'tarea' && $pub['fecha_limite']): ?>
                                    <span class="badge badge-warning">⏰ Límite: <?= fecha($pub['fecha_limite']) ?></span>
                                <?php endif; ?>
                                <?php if ($pub['ya_entregue'] > 0): ?>
                                    <span class="badge badge-success">✅ Entregado</span>
                                <?php endif; ?>
                            </div>
                            <p><?= nl2br(h($pub['contenido'])) ?></p>
                            <?php if (!empty($pub['archivo_url'])): ?>
                                <div class="pub-archivo">
                                    <a href="<?= BASE_URL . $pub['archivo_url'] ?>" target="_blank" class="btn-sm btn-info">
                                        📎 Descargar <?= h($pub['archivo_nombre'] ?? 'Archivo') ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                            <?php if ($pub['tipo_publicacion'] === 'tarea' && $pub['ya_entregue'] == 0): ?>
                                <div class="pub-accion">
                                    <a href="<?= BASE_URL ?>alumno/entregas.php?tarea=<?= $pub['id'] ?>" class="btn-sm btn-primary">
                                        📤 Entregar Tarea
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color:#888;text-align:center;padding:30px 0;">
                    No hay publicaciones disponibles en tus canales.
                </p>
            <?php endif; ?>
        </div>
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
    margin: 8px 0 10px 0;
    font-size: var(--font-size-sm);
    color: var(--color-text);
    line-height: 1.6;
}
.pub-meta {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 8px;
}
.pub-archivo, .pub-accion {
    margin-top: 10px;
}
</style>

<script>
function aplicarFiltros() {
    const canal = document.getElementById('filterCanal').value;
    const tipo = document.getElementById('filterTipo').value;
    const url = new URL(window.location.href);
    if (canal) url.searchParams.set('canal', canal);
    else url.searchParams.delete('canal');
    if (tipo !== 'todos') url.searchParams.set('tipo', tipo);
    else url.searchParams.delete('tipo');
    window.location.href = url.toString();
}

function filtrarPublicaciones() {
    const input = document.getElementById('searchPub');
    const filter = input.value.toUpperCase();
    const items = document.querySelectorAll('.publicacion-item');
    items.forEach(item => {
        const titulo = item.dataset.titulo || '';
        const contenido = item.dataset.contenido || '';
        const text = titulo + ' ' + contenido;
        item.style.display = text.toUpperCase().indexOf(filter) > -1 ? '' : 'none';
    });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>