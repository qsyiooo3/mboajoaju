<?php
// admin/index.php - Panel de administración
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';

requireRole('admin');

$seccion = 'dashboard';
$titulo = 'Panel de Administración';

$total_usuarios = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$total_canales = $pdo->query("SELECT COUNT(*) FROM canales")->fetchColumn();
$total_publicaciones = $pdo->query("SELECT COUNT(*) FROM publicaciones")->fetchColumn();
$total_entregas = $pdo->query("SELECT COUNT(*) FROM entregas")->fetchColumn();
$total_alumnos = $pdo->query("SELECT COUNT(*) FROM alumnos")->fetchColumn();
$total_profesores = $pdo->query("SELECT COUNT(*) FROM profesores")->fetchColumn();

$ultimos_usuarios = $pdo->query("SELECT u.*, 
    CASE WHEN u.rol = 'alumno' THEN a.curso ELSE NULL END as curso,
    CASE WHEN u.rol = 'alumno' THEN a.seccion ELSE NULL END as seccion
    FROM usuarios u 
    LEFT JOIN alumnos a ON u.id = a.id_usuario 
    ORDER BY u.fecha_registro DESC LIMIT 5")->fetchAll();

$canales_recientes = $pdo->query("SELECT c.*, 
    u.nombre_completo as profesor_nombre,
    (SELECT COUNT(*) FROM inscripciones i WHERE i.id_canal = c.id) as total_alumnos
    FROM canales c 
    INNER JOIN profesores pr ON c.id_profesor = pr.id
    INNER JOIN usuarios u ON pr.id_usuario = u.id
    ORDER BY c.fecha_creacion DESC LIMIT 5")->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar_admin.php';
?>

<div class="main-content">
    <div class="topbar">
        <button class="menu-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
        <h1><?= $titulo ?></h1>
        <div class="user-welcome">Bienvenido, <strong><?= escapeOutput($_SESSION['nombre']) ?></strong></div>
    </div>

    <div class="content-body">
        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card stat-primary">
                <div class="stat-icon">👥</div>
                <div class="stat-info">
                    <span class="stat-value"><?= $total_usuarios ?></span>
                    <span class="stat-label">Usuarios</span>
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
                <div class="stat-icon">👨‍🏫</div>
                <div class="stat-info">
                    <span class="stat-value"><?= $total_profesores ?></span>
                    <span class="stat-label">Profesores</span>
                </div>
            </div>
            <div class="stat-card stat-warning">
                <div class="stat-icon">📺</div>
                <div class="stat-info">
                    <span class="stat-value"><?= $total_canales ?></span>
                    <span class="stat-label">Canales</span>
                </div>
            </div>
            <div class="stat-card stat-secondary">
                <div class="stat-icon">📝</div>
                <div class="stat-info">
                    <span class="stat-value"><?= $total_publicaciones ?></span>
                    <span class="stat-label">Publicaciones</span>
                </div>
            </div>
            <div class="stat-card stat-purple">
                <div class="stat-icon">📥</div>
                <div class="stat-info">
                    <span class="stat-value"><?= $total_entregas ?></span>
                    <span class="stat-label">Entregas</span>
                </div>
            </div>
        </div>

        <div class="dashboard-grid">
            <!-- Últimos usuarios -->
            <div class="card">
                <div class="card-header">
                    <h3>👥 Últimos Usuarios Registrados</h3>
                    <a href="<?= URL_BASE ?>admin/usuarios.php" class="btn-sm btn-outline">Ver todos</a>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Correo</th>
                                <th>Rol</th>
                                <th>Curso</th>
                                <th>Registro</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimos_usuarios as $u): ?>
                                <tr>
                                    <td><?= escapeOutput($u['nombre_completo']) ?></td>
                                    <td><?= escapeOutput($u['correo']) ?></td>
                                    <td><span class="badge badge-<?= $u['rol'] === 'admin' ? 'warning' : ($u['rol'] === 'profesor' ? 'info' : 'success') ?>"><?= ucfirst($u['rol']) ?></span></td>
                                    <td><?= ($u['curso'] ?? '-') . ' ' . ($u['seccion'] ?? '') ?></td>
                                    <td style="white-space:nowrap;"><?= timeAgo($u['fecha_registro']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Canales recientes -->
            <div class="card">
                <div class="card-header">
                    <h3>📺 Canales Recientes</h3>
                    <a href="<?= URL_BASE ?>admin/canales.php" class="btn-sm btn-outline">Ver todos</a>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Canal</th>
                                <th>Profesor</th>
                                <th>Alumnos</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($canales_recientes as $c): ?>
                                <tr>
                                    <td><strong><?= escapeOutput($c['nombre']) ?></strong></td>
                                    <td><?= escapeOutput($c['profesor_nombre']) ?></td>
                                    <td><span class="badge badge-info"><?= $c['total_alumnos'] ?></span></td>
                                    <td><span class="badge <?= $c['activo'] ? 'badge-success' : 'badge-danger' ?>"><?= $c['activo'] ? 'Activo' : 'Inactivo' ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Accesos rápidos -->
        <div class="card">
            <h3>⚡ Accesos Rápidos</h3>
            <div class="action-buttons">
                <a href="<?= URL_BASE ?>admin/usuarios.php?accion=crear" class="btn-primary"><i class="fas fa-user-plus"></i> Crear Usuario</a>
                <a href="<?= URL_BASE ?>admin/canales.php" class="btn-secondary"><i class="fas fa-tv"></i> Ver Canales</a>
                <a href="<?= URL_BASE ?>admin/configuracion.php" class="btn-outline"><i class="fas fa-cog"></i> Configuración</a>
            </div>
        </div>
    </div>
</div>

<style>
.dashboard-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--spacing-lg);
}
.card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: var(--spacing-md);
    border-bottom: 1px solid var(--color-border-light);
    margin-bottom: var(--spacing-md);
}
.card-header h3 {
    margin: 0;
    font-size: var(--font-size-md);
}
@media (max-width: 900px) {
    .dashboard-grid { grid-template-columns: 1fr; }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
