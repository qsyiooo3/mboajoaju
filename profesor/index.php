<?php
// ============================================================
// PROFESOR / INDEX.PHP — Mboa' Joaju v2.0
// Dashboard del Profesor
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('profesor');

$seccion = 'dashboard';
$titulo = 'Panel del Profesor';

$id_usuario = (int) $_SESSION['id_usuario'];

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

// ── Estadísticas ──────────────────────────────────────────

// Total de canales del profesor
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM canales WHERE id_profesor = ? AND activo = 1");
$stmt->bind_param("i", $id_profesor);
$stmt->execute();
$total_canales = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Total de publicaciones
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM publicaciones WHERE id_profesor = ?");
$stmt->bind_param("i", $id_profesor);
$stmt->execute();
$total_publicaciones = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Total de alumnos inscritos en sus canales
$stmt = $conn->prepare(
    "SELECT COUNT(DISTINCT i.id_alumno) AS total 
     FROM inscripciones i 
     INNER JOIN canales c ON i.id_canal = c.id 
     WHERE c.id_profesor = ?"
);
$stmt->bind_param("i", $id_profesor);
$stmt->execute();
$total_alumnos = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Entregas pendientes de calificar
$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total 
     FROM entregas e 
     INNER JOIN publicaciones p ON e.id_publicacion = p.id 
     INNER JOIN canales c ON p.id_canal = c.id 
     WHERE c.id_profesor = ? AND e.calificacion IS NULL"
);
$stmt->bind_param("i", $id_profesor);
$stmt->execute();
$pendientes_calificar = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// ── Últimas publicaciones ──────────────────────────────────
$stmt = $conn->prepare(
    "SELECT p.*, c.nombre AS canal_nombre 
     FROM publicaciones p 
     INNER JOIN canales c ON p.id_canal = c.id 
     WHERE p.id_profesor = ? 
     ORDER BY p.fecha_creacion DESC 
     LIMIT 5"
);
$stmt->bind_param("i", $id_profesor);
$stmt->execute();
$ultimas_publicaciones = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ── Entregas recientes ─────────────────────────────────────
$stmt = $conn->prepare(
    "SELECT e.*, p.titulo AS tarea_titulo, u.nombre_completo AS alumno_nombre, c.nombre AS canal_nombre 
     FROM entregas e 
     INNER JOIN publicaciones p ON e.id_publicacion = p.id 
     INNER JOIN canales c ON p.id_canal = c.id 
     INNER JOIN alumnos a ON e.id_alumno = a.id 
     INNER JOIN usuarios u ON a.id_usuario = u.id 
     WHERE c.id_profesor = ? 
     ORDER BY e.fecha_entrega DESC 
     LIMIT 10"
);
$stmt->bind_param("i", $id_profesor);
$stmt->execute();
$entregas_recientes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card stat-primary">
                <div class="stat-icon">📺</div>
                <div class="stat-info">
                    <span class="stat-value"><?= $total_canales ?></span>
                    <span class="stat-label">Mis Canales</span>
                </div>
            </div>
            <div class="stat-card stat-success">
                <div class="stat-icon">🎓</div>
                <div class="stat-info">
                    <span class="stat-value"><?= $total_alumnos ?></span>
                    <span class="stat-label">Alumnos</span>
                </div>
            </div>
            <div class="stat-card stat-info">
                <div class="stat-icon">📝</div>
                <div class="stat-info">
                    <span class="stat-value"><?= $total_publicaciones ?></span>
                    <span class="stat-label">Publicaciones</span>
                </div>
            </div>
            <div class="stat-card stat-danger">
                <div class="stat-icon">⏳</div>
                <div class="stat-info">
                    <span class="stat-value"><?= $pendientes_calificar ?></span>
                    <span class="stat-label">Pendientes Calificar</span>
                </div>
            </div>
        </div>

        <div class="dashboard-grid">
            <!-- Últimas Publicaciones -->
            <div class="card">
                <h3>📝 Últimas Publicaciones</h3>
                <?php if (!empty($ultimas_publicaciones)): ?>
                    <?php foreach ($ultimas_publicaciones as $pub): ?>
                        <div class="activity-item">
                            <div class="activity-icon">📄</div>
                            <div class="activity-content">
                                <strong><?= h($pub['titulo']) ?></strong>
                                <span class="activity-meta">
                                    <?= h($pub['canal_nombre']) ?> · 
                                    <?= fecha($pub['fecha_creacion']) ?>
                                </span>
                                <?php if (!empty($pub['archivo_url'])): ?>
                                    <span class="badge badge-info">📎 Adjunto</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color:#888;text-align:center;">No has publicado aún.</p>
                <?php endif; ?>
            </div>

            <!-- Entregas Recientes -->
            <div class="card">
                <h3>📥 Entregas Recientes</h3>
                <?php if (!empty($entregas_recientes)): ?>
                    <?php foreach ($entregas_recientes as $entrega): ?>
                        <div class="activity-item">
                            <div class="activity-icon">📥</div>
                            <div class="activity-content">
                                <strong><?= h($entrega['alumno_nombre']) ?></strong>
                                <span class="activity-meta">
                                    <?= h($entrega['tarea_titulo']) ?> · 
                                    <?= h($entrega['canal_nombre']) ?>
                                </span>
                                <span class="activity-meta">
                                    <?= fecha($entrega['fecha_entrega']) ?>
                                    <?php if ($entrega['calificacion'] !== null): ?>
                                        · <span class="badge badge-success"><?= $entrega['calificacion'] ?>/10</span>
                                    <?php else: ?>
                                        · <span class="badge badge-warning">Pendiente</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color:#888;text-align:center;">No hay entregas recientes.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>