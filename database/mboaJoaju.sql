-- ============================================================
-- MBOA' JOAJU v2.0 - BASE DE DATOS ESTILO TEAMS SIMPLIFICADO
-- CON SOPORTE PARA CONTRASEÑAS SIMPLES
-- Colegio Técnico Agropecuario "Augusto Roa Bastos"
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

DROP DATABASE IF EXISTS `mboajoaju_teams`;
CREATE DATABASE `mboajoaju_teams` 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

USE `mboajoaju_teams`;

-- ============================================================
-- TABLA: usuarios (Base para autenticación)
-- ============================================================
CREATE TABLE `usuarios` (
    `id`                INT(11)      NOT NULL AUTO_INCREMENT,
    `correo`            VARCHAR(100) NOT NULL COMMENT 'Usuario para login (formato: apellido.nombre)',
    `contrasena`        VARCHAR(255) NOT NULL COMMENT 'Hash bcrypt de la contraseña',
    `rol`               ENUM('admin','profesor','alumno') NOT NULL,
    `nombre_completo`   VARCHAR(100) NOT NULL,
    `avatar`            VARCHAR(255) DEFAULT NULL COMMENT 'URL del avatar',
    `fecha_registro`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `activo`            TINYINT(1)   NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_correo_unico` (`correo`),
    KEY `idx_rol` (`rol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: alumnos (Información personal del estudiante)
-- ============================================================
CREATE TABLE `alumnos` (
    `id`                INT(11)     NOT NULL AUTO_INCREMENT,
    `id_usuario`        INT(11)     NOT NULL,
    `primer_nombre`     VARCHAR(50) NOT NULL,
    `segundo_nombre`    VARCHAR(50) DEFAULT NULL,
    `primer_apellido`   VARCHAR(50) NOT NULL,
    `segundo_apellido`  VARCHAR(50) DEFAULT '',
    `cedula`            VARCHAR(20) NOT NULL COMMENT 'Número de identificación (CI)',
    `telefono_alumno`   VARCHAR(20) DEFAULT NULL,
    `telefono_padre`    VARCHAR(20) DEFAULT NULL COMMENT 'Teléfono del padre/tutor',
    `nombre_padre`      VARCHAR(100) DEFAULT NULL COMMENT 'Nombre del padre/tutor',
    `direccion`         VARCHAR(255) DEFAULT NULL,
    `fecha_nacimiento`  DATE        DEFAULT NULL,
    `curso`             VARCHAR(20)  DEFAULT NULL COMMENT 'Ej: 1ro, 2do, 3ro',
    `seccion`           VARCHAR(10)  DEFAULT NULL COMMENT 'Ej: A, B, C',
    `turno`             ENUM('mañana','tarde','noche') DEFAULT 'mañana',
    `anio_lectivo`      YEAR(4)      DEFAULT NULL,
    `estado`            ENUM('activo','inactivo','suspendido','egresado') NOT NULL DEFAULT 'activo',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_alumno_usuario` (`id_usuario`),
    UNIQUE KEY `idx_alumno_cedula` (`cedula`),
    KEY `idx_alumno_curso` (`curso`, `seccion`),
    KEY `idx_alumno_estado` (`estado`),
    CONSTRAINT `fk_alumnos_usuarios` 
        FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: profesores (Información personal del docente)
-- ============================================================
CREATE TABLE `profesores` (
    `id`                INT(11)     NOT NULL AUTO_INCREMENT,
    `id_usuario`        INT(11)     NOT NULL,
    `primer_nombre`     VARCHAR(50) NOT NULL,
    `segundo_nombre`    VARCHAR(50) DEFAULT NULL,
    `primer_apellido`   VARCHAR(50) NOT NULL,
    `segundo_apellido`  VARCHAR(50) DEFAULT '',
    `cedula`            VARCHAR(20) NOT NULL,
    `telefono`          VARCHAR(20) DEFAULT NULL,
    `telefono_emergencia` VARCHAR(20) DEFAULT NULL,
    `direccion`         VARCHAR(255) DEFAULT NULL,
    `especialidad`      VARCHAR(100) DEFAULT NULL COMMENT 'Ej: Agronomía, Zootecnia',
    `fecha_contratacion` DATE        DEFAULT NULL,
    `estado`            ENUM('activo','inactivo','suspendido') NOT NULL DEFAULT 'activo',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_profesor_usuario` (`id_usuario`),
    UNIQUE KEY `idx_profesor_cedula` (`cedula`),
    CONSTRAINT `fk_profesores_usuarios` 
        FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: canales (Materias/Cursos creados por profesores)
-- ============================================================
CREATE TABLE `canales` (
    `id`                INT(11)      NOT NULL AUTO_INCREMENT,
    `nombre`            VARCHAR(100) NOT NULL COMMENT 'Nombre del canal/materia',
    `descripcion`       TEXT         DEFAULT NULL,
    `id_profesor`       INT(11)      NOT NULL COMMENT 'Profesor que creó el canal',
    `curso`             VARCHAR(20)  DEFAULT NULL COMMENT 'Ej: 1ro, 2do, 3ro',
    `seccion`           VARCHAR(10)  DEFAULT NULL COMMENT 'Ej: A, B, C',
    `color`             VARCHAR(7)   DEFAULT '#1a4a2a' COMMENT 'Color del canal',
    `icono`             VARCHAR(10)  DEFAULT '📚' COMMENT 'Emoji del canal',
    `fecha_creacion`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `activo`            TINYINT(1)   NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `idx_canal_profesor` (`id_profesor`),
    KEY `idx_canal_curso` (`curso`, `seccion`),
    CONSTRAINT `fk_canales_profesores` 
        FOREIGN KEY (`id_profesor`) REFERENCES `profesores` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: inscripciones (Alumnos inscritos en canales)
-- ============================================================
CREATE TABLE `inscripciones` (
    `id`            INT(11)   NOT NULL AUTO_INCREMENT,
    `id_canal`      INT(11)   NOT NULL,
    `id_alumno`     INT(11)   NOT NULL,
    `fecha_insc`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_insc_unica` (`id_canal`,`id_alumno`),
    KEY `idx_insc_alumno` (`id_alumno`),
    CONSTRAINT `fk_insc_canales` 
        FOREIGN KEY (`id_canal`) REFERENCES `canales` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_insc_alumnos` 
        FOREIGN KEY (`id_alumno`) REFERENCES `alumnos` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: publicaciones (Mensajes/Posts de profesores)
-- ============================================================
CREATE TABLE `publicaciones` (
    `id`                INT(11)      NOT NULL AUTO_INCREMENT,
    `id_canal`          INT(11)      NOT NULL,
    `id_profesor`       INT(11)      NOT NULL,
    `titulo`            VARCHAR(200) NOT NULL COMMENT 'Título de la publicación',
    `contenido`         TEXT         NOT NULL COMMENT 'Texto de la publicación',
    `archivo_url`       VARCHAR(500) DEFAULT NULL COMMENT 'URL del archivo adjunto (opcional)',
    `archivo_nombre`    VARCHAR(200) DEFAULT NULL COMMENT 'Nombre original del archivo',
    `tipo_archivo`      VARCHAR(50)  DEFAULT NULL COMMENT 'PDF, DOC, XLS, JPG, etc.',
    `tipo_publicacion`  ENUM('anuncio','tarea','material','general') NOT NULL DEFAULT 'general',
    `fecha_limite`      DATETIME     DEFAULT NULL COMMENT 'Solo para publicaciones tipo tarea',
    `fecha_creacion`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `editado`           TINYINT(1)   NOT NULL DEFAULT 0,
    `fecha_edicion`     TIMESTAMP    NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_pub_canal` (`id_canal`),
    KEY `idx_pub_profesor` (`id_profesor`),
    KEY `idx_pub_fecha` (`fecha_creacion`),
    CONSTRAINT `fk_publicaciones_canales` 
        FOREIGN KEY (`id_canal`) REFERENCES `canales` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_publicaciones_profesores` 
        FOREIGN KEY (`id_profesor`) REFERENCES `profesores` (`id`) 
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: entregas (Alumnos entregan tareas)
-- ============================================================
CREATE TABLE `entregas` (
    `id`                INT(11)        NOT NULL AUTO_INCREMENT,
    `id_publicacion`    INT(11)        NOT NULL,
    `id_alumno`         INT(11)        NOT NULL,
    `archivo_url`       VARCHAR(500)   NOT NULL COMMENT 'URL del archivo entregado',
    `archivo_nombre`    VARCHAR(200)   NOT NULL COMMENT 'Nombre original del archivo',
    `comentario`        TEXT           DEFAULT NULL COMMENT 'Comentario del alumno',
    `fecha_entrega`     TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `calificacion`      DECIMAL(4,2)   DEFAULT NULL,
    `observacion_docente` TEXT         DEFAULT NULL,
    `entregado_tarde`   TINYINT(1)     NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_entrega_unica` (`id_publicacion`,`id_alumno`),
    KEY `idx_entrega_alumno` (`id_alumno`),
    CONSTRAINT `fk_entregas_publicaciones` 
        FOREIGN KEY (`id_publicacion`) REFERENCES `publicaciones` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_entregas_alumnos` 
        FOREIGN KEY (`id_alumno`) REFERENCES `alumnos` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: archivos_canal (Archivos compartidos en el canal)
-- ============================================================
CREATE TABLE `archivos_canal` (
    `id`            INT(11)      NOT NULL AUTO_INCREMENT,
    `id_canal`      INT(11)      NOT NULL,
    `id_profesor`   INT(11)      NOT NULL COMMENT 'Quién subió el archivo',
    `nombre`        VARCHAR(200) NOT NULL,
    `archivo_url`   VARCHAR(500) NOT NULL,
    `descripcion`   TEXT         DEFAULT NULL,
    `fecha_subida`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_archivo_canal` (`id_canal`),
    CONSTRAINT `fk_archivos_canales` 
        FOREIGN KEY (`id_canal`) REFERENCES `canales` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_archivos_profesores` 
        FOREIGN KEY (`id_profesor`) REFERENCES `profesores` (`id`) 
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: logs_actividad (Auditoría)
-- ============================================================
CREATE TABLE `logs_actividad` (
    `id`            INT(11)      NOT NULL AUTO_INCREMENT,
    `id_usuario`    INT(11)      NOT NULL,
    `accion`        VARCHAR(100) NOT NULL COMMENT 'Ej: crear_canal, eliminar_canal, crear_publicacion',
    `detalle`       TEXT         DEFAULT NULL,
    `ip`            VARCHAR(45)  DEFAULT NULL,
    `user_agent`    VARCHAR(255) DEFAULT NULL,
    `fecha`         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_log_usuario` (`id_usuario`),
    KEY `idx_log_fecha` (`fecha`),
    CONSTRAINT `fk_logs_usuarios` 
        FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- VISTAS
-- ============================================================

-- Vista: Publicaciones con información del profesor
CREATE OR REPLACE VIEW `vista_publicaciones` AS
SELECT 
    p.*,
    u.nombre_completo AS profesor_nombre,
    u.avatar AS profesor_avatar,
    c.nombre AS canal_nombre,
    c.id_profesor AS canal_profesor_id,
    (SELECT COUNT(*) FROM entregas e WHERE e.id_publicacion = p.id) AS total_entregas,
    (SELECT COUNT(*) FROM entregas e WHERE e.id_publicacion = p.id AND e.calificacion IS NOT NULL) AS entregas_calificadas
FROM publicaciones p
INNER JOIN profesores pr ON p.id_profesor = pr.id
INNER JOIN usuarios u ON pr.id_usuario = u.id
INNER JOIN canales c ON p.id_canal = c.id
WHERE p.id IS NOT NULL;

-- Vista: Canales con conteo de alumnos
CREATE OR REPLACE VIEW `vista_canales` AS
SELECT 
    c.*,
    u.nombre_completo AS profesor_nombre,
    (SELECT COUNT(*) FROM inscripciones i WHERE i.id_canal = c.id) AS total_alumnos,
    (SELECT COUNT(*) FROM publicaciones p WHERE p.id_canal = c.id) AS total_publicaciones
FROM canales c
INNER JOIN profesores pr ON c.id_profesor = pr.id
INNER JOIN usuarios u ON pr.id_usuario = u.id
WHERE c.activo = 1;

-- Vista: Alumnos con información de usuario
CREATE OR REPLACE VIEW `vista_alumnos` AS
SELECT 
    a.*,
    u.correo,
    u.nombre_completo,
    u.avatar,
    u.fecha_registro,
    u.activo AS usuario_activo
FROM alumnos a
INNER JOIN usuarios u ON a.id_usuario = u.id
WHERE u.rol = 'alumno';

-- Vista: Profesores con información de usuario
CREATE OR REPLACE VIEW `vista_profesores` AS
SELECT 
    p.*,
    u.correo,
    u.nombre_completo,
    u.avatar,
    u.fecha_registro,
    u.activo AS usuario_activo,
    (SELECT COUNT(*) FROM canales c WHERE c.id_profesor = p.id) AS total_canales
FROM profesores p
INNER JOIN usuarios u ON p.id_usuario = u.id
WHERE u.rol = 'profesor';

-- ============================================================
-- FUNCIÓN: Generar usuario a partir de nombre y CI
-- ============================================================
DELIMITER //
CREATE FUNCTION generar_usuario(
    p_apellido VARCHAR(50),
    p_nombre VARCHAR(50),
    p_cedula VARCHAR(20)
) RETURNS VARCHAR(100)
DETERMINISTIC
BEGIN
    RETURN CONCAT(
        LOWER(p_apellido),
        '.',
        LOWER(p_nombre),
        p_cedula
    );
END//
DELIMITER ;

-- ============================================================
-- FUNCIÓN: Generar contraseña inicial (apellido.nombreCI)
-- ============================================================
DELIMITER //
CREATE FUNCTION generar_contrasena_inicial(
    p_apellido VARCHAR(50),
    p_nombre VARCHAR(50),
    p_cedula VARCHAR(20)
) RETURNS VARCHAR(100)
DETERMINISTIC
BEGIN
    RETURN CONCAT(
        LOWER(p_apellido),
        '.',
        LOWER(p_nombre),
        p_cedula
    );
END//
DELIMITER ;

-- ============================================================
-- PROCEDIMIENTO: Crear usuario alumno automáticamente
-- ============================================================
DELIMITER //
CREATE PROCEDURE crear_alumno(
    IN p_primer_nombre VARCHAR(50),
    IN p_primer_apellido VARCHAR(50),
    IN p_cedula VARCHAR(20),
    IN p_curso VARCHAR(20),
    IN p_seccion VARCHAR(10)
)
BEGIN
    DECLARE v_usuario VARCHAR(100);
    DECLARE v_contrasena_plano VARCHAR(100);
    DECLARE v_contrasena_hash VARCHAR(255);
    DECLARE v_id_usuario INT;
    
    -- Generar usuario y contraseña
    SET v_usuario = generar_usuario(p_primer_apellido, p_primer_nombre, p_cedula);
    SET v_contrasena_plano = generar_contrasena_inicial(p_primer_apellido, p_primer_nombre, p_cedula);
    SET v_contrasena_hash = '$2y$12$bXZLJ5Tzp5ZP5ZP5ZP5ZP5u.bXZLJ5Tzp5ZP5ZP5ZP5ZP5ZP5ZP5ZP5ZP5ZP';
    
    -- NOTA: En producción, la contraseña se genera con password_hash()
    -- Este es un ejemplo simplificado
    
    -- Insertar en usuarios
    INSERT INTO usuarios (correo, contrasena, rol, nombre_completo)
    VALUES (v_usuario, v_contrasena_hash, 'alumno', CONCAT(p_primer_nombre, ' ', p_primer_apellido));
    
    SET v_id_usuario = LAST_INSERT_ID();
    
    -- Insertar en alumnos
    INSERT INTO alumnos (id_usuario, primer_nombre, primer_apellido, cedula, curso, seccion)
    VALUES (v_id_usuario, p_primer_nombre, p_primer_apellido, p_cedula, p_curso, p_seccion);
    
    SELECT v_usuario AS usuario_generado, v_contrasena_plano AS contrasena_inicial;
END//
DELIMITER ;

-- ============================================================
-- DATOS INICIALES
-- ============================================================

-- Usuario Admin (contraseña: admin123)
INSERT INTO `usuarios` (`correo`, `contrasena`, `rol`, `nombre_completo`) VALUES
('admin', '$2y$12$bXZLJ5Tzp5ZP5ZP5ZP5ZP5u.bXZLJ5Tzp5ZP5ZP5ZP5ZP5ZP5ZP5ZP5ZP5ZP', 'admin', 'Administrador del Sistema');

-- Profesores de ejemplo (contraseñas generadas automáticamente)
-- Usuario: bogado.carlos1234
INSERT INTO `usuarios` (`correo`, `contrasena`, `rol`, `nombre_completo`) VALUES
('bogado.carlos1234', '$2y$12$bXZLJ5Tzp5ZP5ZP5ZP5ZP5u.bXZLJ5Tzp5ZP5ZP5ZP5ZP5ZP5ZP5ZP5ZP5ZP', 'profesor', 'Carlos Bogado'),
('gonzalez.ana5678', '$2y$12$bXZLJ5Tzp5ZP5ZP5ZP5ZP5u.bXZLJ5Tzp5ZP5ZP5ZP5ZP5ZP5ZP5ZP5ZP5ZP', 'profesor', 'Ana González');

-- Perfiles de profesores
INSERT INTO `profesores` (`id_usuario`, `primer_nombre`, `primer_apellido`, `cedula`, `telefono`, `especialidad`) VALUES
(2, 'Carlos', 'Bogado', '1234', '0981-234-567', 'Agronomía'),
(3, 'Ana', 'González', '5678', '0982-345-678', 'Zootecnia');

-- Alumnos de ejemplo (usando el procedimiento)
-- Usuario: benitez.maria12345, Contraseña: benitez.maria12345
CALL crear_alumno('María', 'Benítez', '12345', '2do', 'A');
-- Usuario: lopez.jose67890, Contraseña: lopez.jose67890
CALL crear_alumno('José', 'López', '67890', '2do', 'A');

-- Canales de ejemplo
INSERT INTO `canales` (`nombre`, `descripcion`, `id_profesor`, `curso`, `seccion`, `color`, `icono`) VALUES
('Agronomía I', 'Fundamentos de agronomía y cultivos', 1, '2do', 'A', '#1a4a2a', '🌾'),
('Zootecnia Básica', 'Introducción a la producción animal', 2, '2do', 'A', '#5d4037', '🐄');

-- Inscripciones de alumnos a canales
INSERT INTO `inscripciones` (`id_canal`, `id_alumno`) VALUES
(1, 1),  -- María inscrita en Agronomía
(1, 2),  -- José inscrito en Agronomía
(2, 1);  -- María inscrita en Zootecnia

-- Publicaciones de ejemplo
INSERT INTO `publicaciones` (`id_canal`, `id_profesor`, `titulo`, `contenido`, `tipo_publicacion`, `fecha_limite`) VALUES
(1, 1, 'Bienvenidos a Agronomía I', 'Hola estudiantes, bienvenidos al curso de Agronomía I. Este es un espacio para compartir materiales y tareas.', 'anuncio', NULL),
(1, 1, 'Tarea 1: Cultivos de temporada', 'Investigar y presentar un informe sobre los cultivos de temporada en la región de Concepción. Entregar en formato PDF.', 'tarea', '2026-10-15 23:59:59'),
(2, 2, 'Introducción a la Zootecnia', 'En esta publicación encontrarán el material de la primera clase sobre producción animal.', 'material', NULL);

-- ============================================================
-- TABLA: notificaciones (Alertas para usuarios)
-- ============================================================
CREATE TABLE IF NOT EXISTS `notificaciones` (
    `id`            INT(11)      NOT NULL AUTO_INCREMENT,
    `id_usuario`    INT(11)      NOT NULL,
    `tipo`          VARCHAR(50)  NOT NULL COMMENT 'tipo de notificacion',
    `titulo`        VARCHAR(200) NOT NULL,
    `contenido`     TEXT         DEFAULT NULL,
    `url`           VARCHAR(500) DEFAULT NULL,
    `leida`         TINYINT(1)   NOT NULL DEFAULT 0,
    `creado_en`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_notif_usuario` (`id_usuario`),
    KEY `idx_notif_leida` (`leida`),
    CONSTRAINT `fk_notif_usuarios` 
        FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;