<?php
// ============================================================
// ALUMNO / CALIFICACIONES.PHP — Mboa' Joaju v2.0
// Ver calificaciones del alumno
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('alumno');

$seccion = 'calificaciones';
$titulo = 'Mis Calificaciones';

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

// ── Obtener todas las calificaciones ──────────────────────
$stmt = $conn->prepare(
    "SELECT e.calificacion, e.observacion_docente, e.fecha_entrega,
            p.titulo AS tarea_titulo, c.nombre AS canal_nombre,
            u.nombre_completo AS profesor_nombre
     FROM entregas e 
     INNER JOIN publicaciones p ON e.id_publicacion = p.id 
     INNER JOIN canales c ON p.id_canal = c.id 
     INNER JOIN profesores pr ON p.id_profesor = pr.id
     INNER JOIN usuarios u ON pr.id_usuario = u.id
     WHERE e.id_alumno = ? AND e.calificacion IS NOT NULL
     ORDER BY e.fecha_entrega DESC"
);
$stmt->bind_param("i", $id_alumno);
$stmt->execute();
$calificaciones = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ── Estadísticas de calificaciones ────────────────────────
$promedio = 0;
$total = count($calificaciones);
if ($total > 0) {
    $suma = array_sum(array_column($calificaciones, 'calificacion'));
    $promedio = round($suma / $total, 1);
}

$aprobadas = 0;
$reprobadas = 0;
foreach ($calificaciones as $c) {
    if ($c['calificacion'] >= 6) {
        $aprobadas++;
    } else {
        $reprobadas++;
    }
}

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
        <div class="stats-grid mini-stats">
            <div class="stat-card stat-primary">
                <div class="stat-icon">📊</div>
                <div class="stat-info">
                    <span class="stat-value"><?= $total ?></span>
                    <span class="stat-label">Total Calificaciones</span>
                </div>
            </div>
            <div class="stat-card stat-success">
                <div class="stat-icon">⭐</div>
                <div class="stat-info">
                    <span class="stat-value"><?= $promedio ?>/10</span>
                    <span class="stat-label">Promedio General</span>
                </div>
            </div>
            <div class="stat-card stat-success">
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <span class="stat-value"><?= $aprobadas ?></span>
                    <span class="stat-label">Aprobadas</span>
                </div>
            </div>
            <div class="stat-card stat-danger">
                <div class="stat-icon">❌</div>
                <div class="stat-info">
                    <span class="stat-value"><?= $reprobadas ?></span>
                    <span class="stat-label">Reprobadas</span>
                </div>
            </div>
        </div>

        <!-- Lista de Calificaciones -->
        <div class="card">
            <h3>📊 Detalle de Calificaciones</h3>
            <?php if (!empty($calificaciones)): ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Tarea</th>
                                <th>Canal</th>
                                <th>Profesor</th>
                                <th>Fecha Entrega</th>
                                <th>Calificación</th>
                                <th>Observación</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($calificaciones as $c): ?>
                                <tr>
                                    <td><strong><?= h($c['tarea_titulo']) ?></strong></td>
                                    <td><?= h($c['canal_nombre']) ?></td>
                                    <td><?= h($c['profesor_nombre']) ?></td>
                                    <td style="font-size:12px;color:#888;"><?= fecha($c['fecha_entrega']) ?></td>
                                    <td>
                                        <span class="badge <?= $c['calificacion'] >= 6 ? 'badge-success' : 'badge-danger' ?>">
                                            <?= number_format($c['calificacion'], 1) ?>/10
                                        </span>
                                    </td>
                                    <td><?= h($c['observacion_docente'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="color:#888;text-align:center;padding:30px 0;">
                    No tienes calificaciones aún.
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.mini-stats .stat-card {
    padding: 12px 16px;
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>