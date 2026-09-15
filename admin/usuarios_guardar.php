<?php
// ============================================================
// ADMIN / USUARIOS_GUARDAR.PHP — Mboa' Joaju v2.0
// Guardar usuario (crear o editar)
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_helper.php';

requireRole('admin');

// ── Verificar POST ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'admin/usuarios.php');
    exit();
}

validarCSRF();

// ── Obtener datos ──────────────────────────────────────────
$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$rol = $_POST['rol'] ?? '';
$nombre_completo = trim($_POST['nombre_completo'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$contrasena = $_POST['contrasena'] ?? '';
$cedula = trim($_POST['cedula'] ?? '');
$curso = trim($_POST['curso'] ?? '');
$seccion = trim($_POST['seccion'] ?? '');
$especialidad = trim($_POST['especialidad'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$telefono_padre = trim($_POST['telefono_padre'] ?? '');
$nombre_padre = trim($_POST['nombre_padre'] ?? '');

// ── Validaciones ───────────────────────────────────────────
$errores = [];

if (!in_array($rol, ['alumno', 'profesor'])) {
    $errores[] = 'Rol inválido.';
}

if (strlen($nombre_completo) < 3) {
    $errores[] = 'El nombre completo debe tener al menos 3 caracteres.';
}

if (strlen($correo) < 3) {
    $errores[] = 'El usuario/correo es inválido.';
}

if ($id === 0 && empty($contrasena) && $rol === 'profesor') {
    $errores[] = 'La contraseña es requerida para nuevos profesores.';
}

if ($rol === 'alumno' && empty($cedula)) {
    $errores[] = 'La cédula es requerida para alumnos.';
}

if ($rol === 'profesor' && empty($cedula)) {
    $errores[] = 'La cédula es requerida para profesores.';
}

// ── Verificar si el correo ya existe (solo para nuevos) ──
if ($id === 0) {
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE correo = ? LIMIT 1");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $errores[] = 'El usuario "' . h($correo) . '" ya existe.';
    }
    $stmt->close();
}

if (!empty($errores)) {
    $_SESSION['error_mensaje'] = implode(' ', $errores);
    $_SESSION['error_tipo'] = 'danger';
    header('Location: ' . BASE_URL . 'admin/usuarios.php');
    exit();
}

// ── Preparar contraseña ────────────────────────────────────
if ($id === 0) {
    // NUEVO USUARIO
    if (empty($contrasena)) {
        // Generar contraseña automática: apellido.nombreCI
        $parts = explode(' ', $nombre_completo);
        $apellido = end($parts);
        $nombre = $parts[0];
        $contrasena_plano = generarContrasenaInicial($apellido, $nombre, $cedula);
    } else {
        $contrasena_plano = $contrasena;
    }
    $contrasena_hash = password_hash($contrasena_plano, PASSWORD_BCRYPT, ['cost' => 12]);
    
    // Insertar usuario
    $conn->begin_transaction();
    
    try {
        $stmt = $conn->prepare(
            "INSERT INTO usuarios (correo, contrasena, rol, nombre_completo) 
             VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param("ssss", $correo, $contrasena_hash, $rol, $nombre_completo);
        $stmt->execute();
        $id_usuario = $conn->insert_id;
        $stmt->close();
        
        // Insertar perfil según rol
        if ($rol === 'alumno') {
            $stmt = $conn->prepare(
                "INSERT INTO alumnos (id_usuario, primer_nombre, primer_apellido, cedula, 
                 curso, seccion, telefono_alumno, telefono_padre, nombre_padre) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $parts = explode(' ', $nombre_completo);
            $primer_nombre = $parts[0];
            $primer_apellido = end($parts);
            $stmt->bind_param(
                "issssssss", 
                $id_usuario, 
                $primer_nombre, 
                $primer_apellido, 
                $cedula, 
                $curso, 
                $seccion, 
                $telefono, 
                $telefono_padre, 
                $nombre_padre
            );
            $stmt->execute();
            $stmt->close();
        } elseif ($rol === 'profesor') {
            $stmt = $conn->prepare(
                "INSERT INTO profesores (id_usuario, primer_nombre, primer_apellido, cedula, 
                 especialidad, telefono) 
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $parts = explode(' ', $nombre_completo);
            $primer_nombre = $parts[0];
            $primer_apellido = end($parts);
            $stmt->bind_param(
                "isssss", 
                $id_usuario, 
                $primer_nombre, 
                $primer_apellido, 
                $cedula, 
                $especialidad, 
                $telefono
            );
            $stmt->execute();
            $stmt->close();
        }
        
        $conn->commit();
        registrarLog($_SESSION['id_usuario'], 'crear_usuario', "Creó usuario: $correo ($rol)");
        
        $_SESSION['error_mensaje'] = "✅ Usuario creado correctamente. Contraseña: <strong>$contrasena_plano</strong>";
        $_SESSION['error_tipo'] = 'success';
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error crear usuario: " . $e->getMessage());
        $_SESSION['error_mensaje'] = 'Error al crear el usuario: ' . $e->getMessage();
        $_SESSION['error_tipo'] = 'danger';
    }
    
} else {
    // EDITAR USUARIO EXISTENTE
    $conn->begin_transaction();
    
    try {
        // Actualizar usuario
        if (!empty($contrasena)) {
            // Cambiar contraseña
            $hash = password_hash($contrasena, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $conn->prepare(
                "UPDATE usuarios SET correo = ?, nombre_completo = ?, contrasena = ? WHERE id = ?"
            );
            $stmt->bind_param("sssi", $correo, $nombre_completo, $hash, $id);
        } else {
            $stmt = $conn->prepare(
                "UPDATE usuarios SET correo = ?, nombre_completo = ? WHERE id = ?"
            );
            $stmt->bind_param("ssi", $correo, $nombre_completo, $id);
        }
        $stmt->execute();
        $stmt->close();
        
        // Actualizar perfil según rol
        if ($rol === 'alumno') {
            $stmt = $conn->prepare(
                "UPDATE alumnos SET 
                 primer_nombre = ?, primer_apellido = ?, cedula = ?, 
                 curso = ?, seccion = ?, telefono_alumno = ?, 
                 telefono_padre = ?, nombre_padre = ? 
                 WHERE id_usuario = ?"
            );
            $parts = explode(' ', $nombre_completo);
            $primer_nombre = $parts[0];
            $primer_apellido = end($parts);
            $stmt->bind_param(
                "ssssssssi", 
                $primer_nombre, 
                $primer_apellido, 
                $cedula, 
                $curso, 
                $seccion, 
                $telefono, 
                $telefono_padre, 
                $nombre_padre, 
                $id
            );
            $stmt->execute();
            $stmt->close();
        } elseif ($rol === 'profesor') {
            $stmt = $conn->prepare(
                "UPDATE profesores SET 
                 primer_nombre = ?, primer_apellido = ?, cedula = ?, 
                 especialidad = ?, telefono = ? 
                 WHERE id_usuario = ?"
            );
            $parts = explode(' ', $nombre_completo);
            $primer_nombre = $parts[0];
            $primer_apellido = end($parts);
            $stmt->bind_param(
                "sssssi", 
                $primer_nombre, 
                $primer_apellido, 
                $cedula, 
                $especialidad, 
                $telefono, 
                $id
            );
            $stmt->execute();
            $stmt->close();
        }
        
        $conn->commit();
        registrarLog($_SESSION['id_usuario'], 'editar_usuario', "Editó usuario ID: $id");
        
        $_SESSION['error_mensaje'] = "✅ Usuario actualizado correctamente.";
        $_SESSION['error_tipo'] = 'success';
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error editar usuario: " . $e->getMessage());
        $_SESSION['error_mensaje'] = 'Error al actualizar el usuario.';
        $_SESSION['error_tipo'] = 'danger';
    }
}

header('Location: ' . BASE_URL . 'admin/usuarios.php');
exit();
?>