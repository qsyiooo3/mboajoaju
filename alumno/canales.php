<?php
// ============================================================
// ALUMNO / CANALES.PHP — Mboa' Joaju v2.0
// Ver canales donde está inscrito el alumno
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('alumno');

$seccion = 'canales';
$titulo = 'Mis Canales';

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

// ── Obtener canales donde está inscrito ────────────────────
$stmt = $conn->prepare(
    "SELECT c.*, 
            u.nombre_completo AS profesor_nombre,
            (SELECT COUNT(*) FROM publicaciones p WHERE p.id_canal = c.id) AS total_publicaciones,
            (SELECT COUNT(*) FROM publicaciones p 
             WHERE p.id_canal = c.id AND p.tipo_publicacion = 'tarea'
               AND NOT EXISTS (
                   SELECT 1 FROM entregas e 
                   WHERE e.id_publicacion = p.id AND e.id_alumno = ?
               )) AS tareas_pendientes
     FROM canales c
     INNER JOIN inscripciones i ON i.id_canal = c.id
     INNER JOIN profesores pr ON c.id_profesor = pr.id
     INNER JOIN usuarios u ON pr.id_usuario = u.id
     WHERE i.id_alumno = ? AND c.activo = 1
     ORDER BY c.nombre"
);
$stmt->bind_param("ii", $id_alumno, $id_alumno);
$stmt->execute();
$canales = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
        <!-- Lista de Canales -->
        <div class="card">
            <h3>📺 Mis Canales</h3>
            <?php if (!empty($canales)): ?>
                <div class="canales-grid">
                    <?php foreach ($canales as $c): ?>
                        <div class="canal-card">
                            <div class="canal-header" style="border-left: 4px solid <?= h($c['color'] ?? '#1a4a2a') ?>;">
                                <span class="canal-icon"><?= h($c['icono'] ?? '📚') ?></span>
                                <div>
                                    <h4><?= h($c['nombre']) ?></h4>
                                    <span class="canal-meta">
                                        👨‍🏫 <?= h($c['profesor_nombre']) ?>
                                    </span>
                                    <span class="canal-meta">
                                        <?= h($c['curso'] ?? '') ?> - <?= h($c['seccion'] ?? '') ?>
                                    </span>
                                </div>
                            </div>
                            <div class="canal-body">
                                <p><?= h(truncar($c['descripcion'] ?? '', 100)) ?></p>
                                <div class="canal-stats">
                                    <span class="badge badge-primary">📝 <?= $c['total_publicaciones'] ?> publicaciones</span>
                                    <?php if ($c['tareas_pendientes'] > 0): ?>
                                        <span class="badge badge-danger">⏳ <?= $c['tareas_pendientes'] ?> tareas pendientes</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="canal-actions">
                                <a href="<?= BASE_URL ?>alumno/publicaciones.php?canal=<?= $c['id'] ?>" class="btn-sm btn-primary">📝 Ver Publicaciones</a>
                                <?php if ($c['tareas_pendientes'] > 0): ?>
                                    <a href="<?= BASE_URL ?>alumno/entregas.php?canal=<?= $c['id'] ?>" class="btn-sm btn-danger">📤 Entregar Tareas</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align:center;padding:40px 0;">
                    <p style="font-size:48px;">📺</p>
                    <p style="color:#888;">Aún no estás inscrito en ningún canal.</p>
                    <p style="color:#888;font-size:13px;">Los profesores te añadirán a sus canales.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.canales-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 16px;
    margin-top: 12px;
}
.canal-card {
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    overflow: hidden;
    transition: box-shadow var(--transition-fast);
}
.canal-card:hover {
    box-shadow: 0 4px 16px var(--color-shadow);
}
.canal-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    background: var(--color-background);
}
.canal-icon {
    font-size: 24px;
}
.canal-header h4 {
    margin: 0;
    font-size: var(--font-size-md);
}
.canal-meta {
    font-size: var(--font-size-xs);
    color: var(--color-text-light);
    display: block;
}
.canal-body {
    padding: 14px 16px;
}
.canal-body p {
    margin: 0 0 10px 0;
    font-size: var(--font-size-sm);
    color: var(--color-text-light);
}
.canal-stats {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.canal-actions {
    padding: 12px 16px;
    border-top: 1px solid var(--color-border-light);
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>