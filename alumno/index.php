<?php
// ============================================================
// ALUMNO / INDEX.PHP — Mboa' Joaju v2.0
// Dashboard del Alumno
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('alumno');

$seccion = 'dashboard';
$titulo = 'Mi Panel';

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

// ── Estadísticas ──────────────────────────────────────────

// Total de canales donde está inscrito
$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total FROM inscripciones WHERE id_alumno = ?"
);
$stmt->bind_param("i", $id_alumno);
$stmt->execute();
$total_canales = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Tareas pendientes (publicaciones tipo tarea sin entregar)
$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total 
     FROM publicaciones p 
     INNER JOIN canales c ON p.id_canal = c.id 
     INNER JOIN inscripciones i ON i.id_canal = c.id 
     WHERE i.id_alumno = ? 
       AND p.tipo_publicacion = 'tarea' 
       AND (p.fecha_limite IS NULL OR p.fecha_limite > NOW())
       AND NOT EXISTS (
           SELECT 1 FROM entregas e 
           WHERE e.id_publicacion = p.id AND e.id_alumno = ?
       )"
);
$stmt->bind_param("ii", $id_alumno, $id_alumno);
$stmt->execute();
$tareas_pendientes = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Tareas entregadas
$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total 
     FROM entregas e 
     WHERE e.id_alumno = ?"
);
$stmt->bind_param("i", $id_alumno);
$stmt->execute();
$tareas_entregadas = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Publicaciones no leídas (avisos/mensajes de profesores)
$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total 
     FROM publicaciones p 
     INNER JOIN canales c ON p.id_canal = c.id 
     INNER JOIN inscripciones i ON i.id_canal = c.id 
     WHERE i.id_alumno = ? 
       AND p.tipo_publicacion != 'tarea'
       AND p.fecha_creacion > DATE_SUB(NOW(), INTERVAL 7 DAY)"
);
$stmt->bind_param("i", $id_alumno);
$stmt->execute();
$nuevas_publicaciones = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// ── Últimas publicaciones en sus canales ──────────────────
$stmt = $conn->prepare(
    "SELECT p.*, c.nombre AS canal_nombre, u.nombre_completo AS profesor_nombre
     FROM publicaciones p 
     INNER JOIN canales c ON p.id_canal = c.id 
     INNER JOIN inscripciones i ON i.id_canal = c.id 
     INNER JOIN profesores pr ON p.id_profesor = pr.id
     INNER JOIN usuarios u ON pr.id_usuario = u.id
     WHERE i.id_alumno = ? 
     ORDER BY p.fecha_creacion DESC 
     LIMIT 5"
);
$stmt->bind_param("i", $id_alumno);
$stmt->execute();
$ultimas_publicaciones = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ── Tareas pendientes con detalles ────────────────────────
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
     ORDER BY p.fecha_limite ASC 
     LIMIT 5"
);
$stmt->bind_param("ii", $id_alumno, $id_alumno);
$stmt->execute();
$tareas_pendientes_lista = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card stat-primary">
                <div class="stat-icon">📺</div>
                <div class="stat-info">
                    <span class="stat-value"><?= $total_canales ?></span>
                    <span class="stat-label">Mis Canales</span>
                </div>
            </div>
            <div class="stat-card stat-danger">
                <div class="stat-icon">⏳</div>
                <div class="stat-info">
                    <span class="stat-value"><?= $tareas_pendientes ?></span>
                    <span class="stat-label">Tareas Pendientes</span>
                </div>
            </div>
            <div class="stat-card stat-success">
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <span class="stat-value"><?= $tareas_entregadas ?></span>
                    <span class="stat-label">Tareas Entregadas</span>
                </div>
            </div>
            <div class="stat-card stat-info">
                <div class="stat-icon">📢</div>
                <div class="stat-info">
                    <span class="stat-value"><?= $nuevas_publicaciones ?></span>
                    <span class="stat-label">Publicaciones Nuevas</span>
                </div>
            </div>
        </div>

        <div class="dashboard-grid">
            <!-- Tareas Pendientes -->
            <div class="card">
                <h3>⏳ Tareas Pendientes</h3>
                <?php if (!empty($tareas_pendientes_lista)): ?>
                    <?php foreach ($tareas_pendientes_lista as $tarea): ?>
                        <div class="activity-item task-pendiente">
                            <div class="activity-icon">📝</div>
                            <div class="activity-content">
                                <strong><?= h($tarea['titulo']) ?></strong>
                                <span class="activity-meta">
                                    <?= h($tarea['canal_nombre']) ?> · 
                                    <?= h($tarea['profesor_nombre']) ?>
                                </span>
                                <?php if ($tarea['fecha_limite']): ?>
                                    <span class="activity-meta" style="color:var(--color-danger);">
                                        ⏰ Límite: <?= fecha($tarea['fecha_limite']) ?>
                                    </span>
                                <?php endif; ?>
                                <a href="<?= BASE_URL ?>alumno/entregas.php?tarea=<?= $tarea['id'] ?>" class="btn-sm btn-primary" style="margin-top:4px;">
                                    📤 Entregar
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color:#888;text-align:center;">🎉 No tienes tareas pendientes.</p>
                <?php endif; ?>
            </div>

            <!-- Últimas Publicaciones -->
            <div class="card">
                <h3>📢 Novedades</h3>
                <?php if (!empty($ultimas_publicaciones)): ?>
                    <?php foreach ($ultimas_publicaciones as $pub): ?>
                        <div class="activity-item">
                            <div class="activity-icon">📄</div>
                            <div class="activity-content">
                                <strong><?= h($pub['titulo']) ?></strong>
                                <span class="activity-meta">
                                    <?= h($pub['canal_nombre']) ?> · 
                                    <?= h($pub['profesor_nombre']) ?> · 
                                    <?= fecha($pub['fecha_creacion']) ?>
                                </span>
                                <?php if (!empty($pub['archivo_url'])): ?>
                                    <span class="badge badge-info">📎 Adjunto</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color:#888;text-align:center;">No hay novedades recientes.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.task-pendiente {
    border-left: 3px solid var(--color-danger);
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>