<?php
// ============================================================
// ALUMNO / PERFIL.PHP — Mboa' Joaju v2.0
// Perfil del alumno
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('alumno');

$seccion = 'perfil';
$titulo = 'Mi Perfil';
$mensaje = '';
$tipo_alerta = '';

$id_usuario = (int) $_SESSION['id_usuario'];
$csrf_token = $_SESSION['csrf_token'];

// ── Obtener datos del alumno ──────────────────────────────
$stmt = $conn->prepare(
    "SELECT a.*, u.correo, u.nombre_completo, u.fecha_registro 
     FROM alumnos a 
     INNER JOIN usuarios u ON a.id_usuario = u.id 
     WHERE a.id_usuario = ?"
);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();
$alumno = $result->fetch_assoc();
$stmt->close();

if (!$alumno) {
    header('Location: ' . BASE_URL . 'index.php');
    exit();
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
        <?php if (!empty($mensaje)): ?>
            <?= mostrarAlerta($mensaje, $tipo_alerta) ?>
        <?php endif; ?>

        <div class="profile-grid">
            <!-- Información Personal -->
            <div class="card">
                <h3>👤 Información Personal</h3>
                <div class="profile-field">
                    <span class="field-label">Nombre Completo</span>
                    <span class="field-value"><?= h($alumno['nombre_completo']) ?></span>
                </div>
                <div class="profile-field">
                    <span class="field-label">Usuario</span>
                    <span class="field-value"><code><?= h($alumno['correo']) ?></code></span>
                </div>
                <div class="profile-field">
                    <span class="field-label">Cédula</span>
                    <span class="field-value"><?= h($alumno['cedula'] ?? 'No registrada') ?></span>
                </div>
                <div class="profile-field">
                    <span class="field-label">Teléfono</span>
                    <span class="field-value"><?= h($alumno['telefono_alumno'] ?? 'No registrado') ?></span>
                </div>
                <div class="profile-field">
                    <span class="field-label">Fecha de Nacimiento</span>
                    <span class="field-value"><?= $alumno['fecha_nacimiento'] ? fecha($alumno['fecha_nacimiento'], 'd/m/Y') : 'No registrada' ?></span>
                </div>
            </div>

            <!-- Información Académica -->
            <div class="card">
                <h3>📚 Información Académica</h3>
                <div class="profile-field">
                    <span class="field-label">Curso</span>
                    <span class="field-value"><?= h($alumno['curso'] ?? 'No asignado') ?></span>
                </div>
                <div class="profile-field">
                    <span class="field-label">Sección</span>
                    <span class="field-value"><?= h($alumno['seccion'] ?? 'No asignada') ?></span>
                </div>
                <div class="profile-field">
                    <span class="field-label">Turno</span>
                    <span class="field-value"><?= ucfirst(h($alumno['turno'] ?? 'No asignado')) ?></span>
                </div>
                <div class="profile-field">
                    <span class="field-label">Año Lectivo</span>
                    <span class="field-value"><?= h($alumno['anio_lectivo'] ?? 'No asignado') ?></span>
                </div>
                <div class="profile-field">
                    <span class="field-label">Estado</span>
                    <span class="field-value">
                        <span class="badge <?= badgeEstado($alumno['estado']) ?>">
                            <?= nombreEstado($alumno['estado']) ?>
                        </span>
                    </span>
                </div>
                <div class="profile-field">
                    <span class="field-label">Fecha de Registro</span>
                    <span class="field-value"><?= fecha($alumno['fecha_registro']) ?></span>
                </div>
            </div>

            <!-- Información del Padre/Tutor -->
            <div class="card full-width">
                <h3>👨‍👩‍👦 Padre / Tutor</h3>
                <div class="profile-field">
                    <span class="field-label">Nombre</span>
                    <span class="field-value"><?= h($alumno['nombre_padre'] ?? 'No registrado') ?></span>
                </div>
                <div class="profile-field">
                    <span class="field-label">Teléfono</span>
                    <span class="field-value"><?= h($alumno['telefono_padre'] ?? 'No registrado') ?></span>
                </div>
                <div class="profile-field">
                    <span class="field-label">Dirección</span>
                    <span class="field-value"><?= h($alumno['direccion'] ?? 'No registrada') ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.profile-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
}
.profile-grid .full-width {
    grid-column: 1 / -1;
}
.profile-field {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid var(--color-border-light);
}
.profile-field:last-child {
    border-bottom: none;
}
.field-label {
    font-weight: 600;
    color: var(--color-text-light);
    font-size: var(--font-size-sm);
}
.field-value {
    color: var(--color-text);
    font-size: var(--font-size-sm);
}
@media (max-width: 768px) {
    .profile-grid {
        grid-template-columns: 1fr;
    }
    .profile-field {
        flex-direction: column;
        gap: 4px;
    }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>