<?php
// ============================================================
// ADMIN / CANALES_GUARDAR.PHP — Mboa' Joaju v2.0
// Guardar canal (crear o editar) - SOLO ADMIN
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'admin/canales.php');
    exit();
}

validarCSRF();

$id          = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$nombre      = trim($_POST['nombre'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$id_profesor = (int) ($_POST['id_profesor'] ?? 0);
$curso       = trim($_POST['curso'] ?? '');
$seccion     = trim($_POST['seccion'] ?? '');
$color       = trim($_POST['color'] ?? '#1a4a2a');
$icono       = trim($_POST['icono'] ?? '📚');

$errores = [];
if (strlen($nombre) < 3) $errores[] = 'El nombre del canal debe tener al menos 3 caracteres.';
if ($id_profesor <= 0)   $errores[] = 'Debes seleccionar un profesor responsable.';
if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) $color = '#1a4a2a';

// Verificar profesor
$profesor = null;
if ($id_profesor > 0) {
    $stmt = $conn->prepare(
        "SELECT p.id, u.nombre_completo 
         FROM profesores p 
         INNER JOIN usuarios u ON p.id_usuario = u.id 
         WHERE p.id = ? AND p.estado = 'activo' AND u.activo = 1"
    );
    $stmt->bind_param("i", $id_profesor);
    $stmt->execute();
    $profesor = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$profesor) $errores[] = 'El profesor seleccionado no existe o no está activo.';
}

if (!empty($errores)) {
    $_SESSION['error_mensaje'] = implode(' ', $errores);
    $_SESSION['error_tipo'] = 'danger';
    header('Location: ' . BASE_URL . 'admin/canales.php');
    exit();
}

if ($id === 0) {
    // CREAR
    $stmt = $conn->prepare(
        "INSERT INTO canales (nombre, descripcion, id_profesor, curso, seccion, color, icono, activo)
         VALUES (?, ?, ?, ?, ?, ?, ?, 1)"
    );
    $stmt->bind_param("ssissss", $nombre, $descripcion, $id_profesor, $curso, $seccion, $color, $icono);

    if ($stmt->execute()) {
        $nuevo_id = $conn->insert_id;
        registrarLog($_SESSION['id_usuario'], 'crear_canal', "Admin creó canal: $nombre (ID: $nuevo_id)");
        $_SESSION['error_mensaje'] = "✅ Canal \"$nombre\" creado correctamente.";
        $_SESSION['error_tipo'] = 'success';
    } else {
        error_log("Error admin crear canal: " . $conn->error);
        $_SESSION['error_mensaje'] = '❌ Error al crear el canal.';
        $_SESSION['error_tipo'] = 'danger';
    }
    $stmt->close();

} else {
    // EDITAR
    $stmt = $conn->prepare("SELECT id, nombre FROM canales WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $canal_existente = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$canal_existente) {
        $_SESSION['error_mensaje'] = 'El canal no existe.';
        $_SESSION['error_tipo'] = 'danger';
        header('Location: ' . BASE_URL . 'admin/canales.php');
        exit();
    }

    $stmt = $conn->prepare(
        "UPDATE canales SET nombre=?, descripcion=?, id_profesor=?, curso=?, seccion=?, color=?, icono=? WHERE id=?"
    );
    $stmt->bind_param("ssissssi", $nombre, $descripcion, $id_profesor, $curso, $seccion, $color, $icono, $id);

    if ($stmt->execute()) {
        registrarLog($_SESSION['id_usuario'], 'editar_canal', "Admin editó canal ID: $id ($nombre)");
        $_SESSION['error_mensaje'] = "✅ Canal \"$nombre\" actualizado correctamente.";
        $_SESSION['error_tipo'] = 'success';
    } else {
        error_log("Error admin editar canal: " . $conn->error);
        $_SESSION['error_mensaje'] = '❌ Error al actualizar el canal.';
        $_SESSION['error_tipo'] = 'danger';
    }
    $stmt->close();
}

header('Location: ' . BASE_URL . 'admin/canales.php');
exit();
?>