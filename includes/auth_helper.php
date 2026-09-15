<?php
// ============================================================
// INCLUDES / AUTH_HELPER.PHP — Mboa' Joaju v2.0
// Funciones para generar credenciales y crear usuarios
// ============================================================

/**
 * Genera el nombre de usuario a partir de apellido, nombre y cédula
 * Formato: apellido.nombreCI (todo en minúsculas, sin espacios)
 * Ejemplo: benitez.maria12345
 */
function generarUsuario(string $apellido, string $nombre, string $cedula): string {
    $apellido = strtolower(limpiarTexto($apellido));
    $nombre = strtolower(limpiarTexto($nombre));
    $cedula = preg_replace('/[^0-9]/', '', $cedula);
    return $apellido . '.' . $nombre . $cedula;
}

/**
 * Genera la contraseña inicial (mismo formato que el usuario)
 * Ejemplo: benitez.maria12345
 */
function generarContrasenaInicial(string $apellido, string $nombre, string $cedula): string {
    return generarUsuario($apellido, $nombre, $cedula);
}

/**
 * Limpia texto: quita acentos, espacios y caracteres especiales
 */
function limpiarTexto(string $texto): string {
    $texto = trim($texto);
    // Reemplazar caracteres acentuados
    $acentos = [
        'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n',
        'Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ñ'=>'N',
        'ä'=>'a','ë'=>'e','ï'=>'i','ö'=>'o','ü'=>'u',
        'Ä'=>'A','Ë'=>'E','Ï'=>'I','Ö'=>'O','Ü'=>'U'
    ];
    $texto = strtr($texto, $acentos);
    // Solo permitir letras
    $texto = preg_replace('/[^a-zA-Z]/', '', $texto);
    return $texto;
}

/**
 * Crea un alumno completo (usuario + perfil)
 * Retorna array con: exito, mensaje, usuario, contrasena, id_usuario
 */
function crearAlumnoCompleto(
    string $primer_nombre,
    string $primer_apellido,
    string $cedula,
    ?string $curso = null,
    ?string $seccion = null,
    ?string $telefono = null,
    ?string $telefono_padre = null,
    ?string $nombre_padre = null,
    ?string $contrasena_custom = null
): array {
    global $conn;

    // Validaciones
    if (empty($primer_nombre) || empty($primer_apellido) || empty($cedula)) {
        return ['exito' => false, 'mensaje' => 'Nombre, apellido y cédula son obligatorios.'];
    }

    // Generar credenciales
    $usuario = generarUsuario($primer_apellido, $primer_nombre, $cedula);
    $contrasena_plano = !empty($contrasena_custom)
        ? $contrasena_custom
        : generarContrasenaInicial($primer_apellido, $primer_nombre, $cedula);
    $contrasena_hash = password_hash($contrasena_plano, PASSWORD_BCRYPT, ['cost' => 12]);

    // Verificar que el usuario no exista
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE correo = ? LIMIT 1");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        return [
            'exito' => false,
            'mensaje' => 'El usuario ya existe: ' . $usuario
        ];
    }
    $stmt->close();

    // Verificar que la cédula no esté registrada
    $stmt = $conn->prepare("SELECT id FROM alumnos WHERE cedula = ? LIMIT 1");
    $stmt->bind_param("s", $cedula);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        return [
            'exito' => false,
            'mensaje' => 'La cédula ya está registrada: ' . $cedula
        ];
    }
    $stmt->close();

    // Transacción
    $conn->begin_transaction();

    try {
        // Insertar en usuarios
        $stmt = $conn->prepare(
            "INSERT INTO usuarios (correo, contrasena, rol, nombre_completo, activo)
             VALUES (?, ?, 'alumno', ?, 1)"
        );
        $nombre_completo = $primer_nombre . ' ' . $primer_apellido;
        $stmt->bind_param("sss", $usuario, $contrasena_hash, $nombre_completo);
        $stmt->execute();
        $id_usuario = $conn->insert_id;
        $stmt->close();

        // Insertar en alumnos
        $stmt = $conn->prepare(
            "INSERT INTO alumnos (
                id_usuario, primer_nombre, primer_apellido, cedula,
                curso, seccion, telefono_alumno, telefono_padre, nombre_padre
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
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

        $conn->commit();

        return [
            'exito'      => true,
            'mensaje'    => 'Alumno creado correctamente.',
            'usuario'    => $usuario,
            'contrasena' => $contrasena_plano,
            'id_usuario' => $id_usuario
        ];

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error crear alumno: " . $e->getMessage());
        return [
            'exito' => false,
            'mensaje' => 'Error al crear el alumno. Contacte al administrador.'
        ];
    }
}

/**
 * Crea un profesor completo (usuario + perfil)
 * Retorna array con: exito, mensaje, usuario, contrasena, id_usuario
 */
function crearProfesorCompleto(
    string $primer_nombre,
    string $primer_apellido,
    string $cedula,
    ?string $especialidad = null,
    ?string $telefono = null,
    ?string $contrasena_custom = null
): array {
    global $conn;

    // Validaciones
    if (empty($primer_nombre) || empty($primer_apellido) || empty($cedula)) {
        return ['exito' => false, 'mensaje' => 'Nombre, apellido y cédula son obligatorios.'];
    }

    // Generar credenciales
    $usuario = generarUsuario($primer_apellido, $primer_nombre, $cedula);
    $contrasena_plano = !empty($contrasena_custom)
        ? $contrasena_custom
        : generarContrasenaInicial($primer_apellido, $primer_nombre, $cedula);
    $contrasena_hash = password_hash($contrasena_plano, PASSWORD_BCRYPT, ['cost' => 12]);

    // Verificar que el usuario no exista
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE correo = ? LIMIT 1");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        return [
            'exito' => false,
            'mensaje' => 'El usuario ya existe: ' . $usuario
        ];
    }
    $stmt->close();

    // Verificar que la cédula no esté registrada
    $stmt = $conn->prepare("SELECT id FROM profesores WHERE cedula = ? LIMIT 1");
    $stmt->bind_param("s", $cedula);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        return [
            'exito' => false,
            'mensaje' => 'La cédula ya está registrada: ' . $cedula
        ];
    }
    $stmt->close();

    // Transacción
    $conn->begin_transaction();

    try {
        // Insertar en usuarios
        $stmt = $conn->prepare(
            "INSERT INTO usuarios (correo, contrasena, rol, nombre_completo, activo)
             VALUES (?, ?, 'profesor', ?, 1)"
        );
        $nombre_completo = $primer_nombre . ' ' . $primer_apellido;
        $stmt->bind_param("sss", $usuario, $contrasena_hash, $nombre_completo);
        $stmt->execute();
        $id_usuario = $conn->insert_id;
        $stmt->close();

        // Insertar en profesores
        $stmt = $conn->prepare(
            "INSERT INTO profesores (
                id_usuario, primer_nombre, primer_apellido, cedula,
                especialidad, telefono
            ) VALUES (?, ?, ?, ?, ?, ?)"
        );
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

        $conn->commit();

        return [
            'exito'      => true,
            'mensaje'    => 'Profesor creado correctamente.',
            'usuario'    => $usuario,
            'contrasena' => $contrasena_plano,
            'id_usuario' => $id_usuario
        ];

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error crear profesor: " . $e->getMessage());
        return [
            'exito' => false,
            'mensaje' => 'Error al crear el profesor. Contacte al administrador.'
        ];
    }
}

/**
 * Restablece la contraseña de un usuario a su valor inicial
 * (apellido.nombreCI)
 */
function restablecerContrasena(int $id_usuario): array {
    global $conn;

    // Obtener datos del usuario y su perfil
    $stmt = $conn->prepare(
        "SELECT u.correo, u.rol,
                a.primer_nombre AS a_nombre, a.primer_apellido AS a_apellido, a.cedula AS a_cedula,
                p.primer_nombre AS p_nombre, p.primer_apellido AS p_apellido, p.cedula AS p_cedula
         FROM usuarios u
         LEFT JOIN alumnos a ON u.id = a.id_usuario
         LEFT JOIN profesores p ON u.id = p.id_usuario
         WHERE u.id = ?"
    );
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $usuario = $resultado->fetch_assoc();
    $stmt->close();

    if (!$usuario) {
        return ['exito' => false, 'mensaje' => 'Usuario no encontrado.'];
    }

    // Determinar nombre, apellido y cédula según rol
    if ($usuario['rol'] === 'alumno') {
        $nombre = $usuario['a_nombre'];
        $apellido = $usuario['a_apellido'];
        $cedula = $usuario['a_cedula'];
    } elseif ($usuario['rol'] === 'profesor') {
        $nombre = $usuario['p_nombre'];
        $apellido = $usuario['p_apellido'];
        $cedula = $usuario['p_cedula'];
    } else {
        return ['exito' => false, 'mensaje' => 'No se puede restablecer la contraseña del administrador.'];
    }

    // Generar nueva contraseña
    $nueva_contrasena = generarContrasenaInicial($apellido, $nombre, $cedula);
    $hash = password_hash($nueva_contrasena, PASSWORD_BCRYPT, ['cost' => 12]);

    // Actualizar
    $stmt = $conn->prepare("UPDATE usuarios SET contrasena = ? WHERE id = ?");
    $stmt->bind_param("si", $hash, $id_usuario);
    $stmt->execute();
    $stmt->close();

    return [
        'exito'            => true,
        'mensaje'          => 'Contraseña restablecida correctamente.',
        'nueva_contrasena' => $nueva_contrasena,
        'usuario'          => $usuario['correo']
    ];
}

/**
 * Verifica si un correo ya existe
 */
function existeUsuario(string $correo, int $excluir_id = 0): bool {
    global $conn;

    if ($excluir_id > 0) {
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE correo = ? AND id != ? LIMIT 1");
        $stmt->bind_param("si", $correo, $excluir_id);
    } else {
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE correo = ? LIMIT 1");
        $stmt->bind_param("s", $correo);
    }

    $stmt->execute();
    $stmt->store_result();
    $existe = $stmt->num_rows > 0;
    $stmt->close();

    return $existe;
}
?>