<?php
// ============================================================
// ADMIN / USUARIOS_OBTENER.PHP — Mboa' Joaju v2.0
// Obtener datos de usuario para edición (AJAX)
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';

requireRole('admin');

header('Content-Type: application/json');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    echo json_encode(['exito' => false, 'mensaje' => 'ID inválido']);
    exit();
}

// Obtener datos del usuario
$stmt = $conn->prepare(
    "SELECT u.id, u.correo, u.nombre_completo, u.rol, u.activo,
            a.id AS alumno_id, a.cedula AS a_cedula, a.curso, a.seccion,
            a.telefono_alumno, a.telefono_padre, a.nombre_padre,
            p.id AS profesor_id, p.cedula AS p_cedula, p.especialidad, p.telefono
     FROM usuarios u
     LEFT JOIN alumnos a ON u.id = a.id_usuario
     LEFT JOIN profesores p ON u.id = p.id_usuario
     WHERE u.id = ?"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();
$stmt->close();

if (!$usuario) {
    echo json_encode(['exito' => false, 'mensaje' => 'Usuario no encontrado']);
    exit();
}

// Preparar datos para respuesta
$data = [
    'exito' => true,
    'id' => $usuario['id'],
    'correo' => $usuario['correo'],
    'nombre_completo' => $usuario['nombre_completo'],
    'rol' => $usuario['rol'],
    'activo' => $usuario['activo'],
    'cedula' => $usuario['a_cedula'] ?? $usuario['p_cedula'] ?? '',
    'curso' => $usuario['curso'] ?? '',
    'seccion' => $usuario['seccion'] ?? '',
    'especialidad' => $usuario['especialidad'] ?? '',
    'telefono' => $usuario['telefono'] ?? $usuario['telefono_alumno'] ?? '',
    'telefono_padre' => $usuario['telefono_padre'] ?? '',
    'nombre_padre' => $usuario['nombre_padre'] ?? ''
];

echo json_encode($data);
?>