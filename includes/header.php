<?php
// ============================================================
// INCLUDES / HEADER.PHP — Mboa' Joaju v2.0
// Cabecera HTML con favicons y metadatos
// ============================================================

if (!defined('BASE_URL')) {
    define('BASE_URL', URL_BASE);
}
if (!isset($titulo)) $titulo = APP_NAME;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo ?> — CTA "Augusto Roa Bastos"</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <!-- Favicons -->
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>assets/favicon/favicon.ico">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= BASE_URL ?>assets/favicon/favicon-16x16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= BASE_URL ?>assets/favicon/favicon-32x32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= BASE_URL ?>assets/favicon/apple-touch-icon.png">
    <link rel="manifest" href="<?= BASE_URL ?>assets/favicon/site.webmanifest">
    <meta name="theme-color" content="#1a4a2a">
    <meta name="msapplication-TileColor" content="#1a4a2a">

    <!-- Metadatos -->
    <meta name="description" content="Plataforma Educativa CTA Augusto Roa Bastos — Mboa' Joaju">
    <meta name="author" content="Mboa' Joaju">
    <meta name="robots" content="noindex, nofollow">

    <!-- Open Graph -->
    <meta property="og:title" content="Mboa' Joaju — CTA Augusto Roa Bastos">
    <meta property="og:description" content="Plataforma Educativa del Colegio Técnico Agropecuario">
    <meta property="og:image" content="<?= BASE_URL ?>assets/images/logos/logo.png">
    <meta property="og:url" content="<?= BASE_URL ?>">
    <meta property="og:type" content="website">

    <!-- Estilos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/error-pages.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/print.css?v=<?= time() ?>" media="print">
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
