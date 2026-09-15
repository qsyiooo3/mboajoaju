<?php
// ============================================================
// ERROR / 404.PHP — Mboa' Joaju v2.0
// Página no encontrada
// ============================================================

require_once __DIR__ . '/../config/config.php';

http_response_code(404);

// Detectar si viene de una sesión activa
$tiene_sesion = isset($_SESSION['rol']);
$nombre = $_SESSION['nombre'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Página no encontrada | Mboa' Joaju</title>
    <meta name="robots" content="noindex, nofollow">

    <!-- Favicons -->
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>assets/favicon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= BASE_URL ?>assets/favicon/favicon-32x32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= BASE_URL ?>assets/favicon/apple-touch-icon.png">
    <meta name="theme-color" content="#1a4a2a">

    <!-- Estilos -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/error-pages.css?v=<?= time() ?>">
</head>
<body class="error-page">

<div class="error-container">
    <!-- Logo -->
    <img src="<?= BASE_URL ?>assets/images/logos/logo.png"
         alt="Mboa' Joaju"
         class="error-logo">

    <!-- Código de error -->
    <div class="error-code">404</div>

    <!-- Título -->
    <h1 class="error-title">Página no encontrada</h1>

    <!-- Descripción -->
    <p class="error-description">
        Lo sentimos, la página que estás buscando no existe
        o ha sido movida a otra ubicación.
    </p>

    <!-- Botón volver -->
    <?php if ($tiene_sesion): ?>
        <?php
        $destino = match ($_SESSION['rol']) {
            'admin'    => BASE_URL . 'admin/index.php',
            'profesor' => BASE_URL . 'profesor/index.php',
            'alumno'   => BASE_URL . 'alumno/index.php',
            default    => BASE_URL . 'index.php'
        };
        ?>
        <a href="<?= $destino ?>" class="error-btn">
            🏠 Volver al inicio
        </a>
    <?php else: ?>
        <a href="<?= BASE_URL ?>auth/login.php" class="error-btn">
            🔐 Ir al inicio de sesión
        </a>
    <?php endif; ?>

    <!-- Pie -->
    <div class="error-footer">
        <strong>Mboa' Joaju</strong> — CTA "Augusto Roa Bastos"<br>
        Si el problema persiste, contacta al administrador.
    </div>
</div>

</body>
</html>