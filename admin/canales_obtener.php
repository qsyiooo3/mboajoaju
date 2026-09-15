<?php
// ============================================================
// ADMIN / CANALES_OBTENER.PHP — Mboa' Joaju v2.0
// Obtener datos de canal para editar (AJAX)
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

header('Content-Type: application/json');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    echo json_encode(['exito' => false, 'mensaje' => 'ID inválido']);
    exit();
}

$stmt = $conn->prepare(
    "SELECT id, nombre, descripcion, id_profesor, curso, seccion, color, icono, activo
     FROM canales WHERE id = ?"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$canal = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$canal) {
    echo json_encode(['exito' => false, 'mensaje' => 'Canal no encontrado']);
    exit();
}

echo json_encode([
    'exito'       => true,
    'id'          => (int) $canal['id'],
    'nombre'      => $canal['nombre'],
    'descripcion' => $canal['descripcion'] ?? '',
    'id_profesor' => (int) $canal['id_profesor'],
    'curso'       => $canal['curso'] ?? '',
    'seccion'     => $canal['seccion'] ?? '',
    'color'       => $canal['color'] ?? '#1a4a2a',
    'icono'       => $canal['icono'] ?? '📚',
    'activo'      => (int) $canal['activo']
]);
?>