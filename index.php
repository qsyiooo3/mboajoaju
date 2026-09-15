<?php
// index.php - Página principal (redirige según sesión)
require_once __DIR__ . '/config/config.php';

if (isset($_SESSION['usuario_id']) && isset($_SESSION['rol']) && isset($_SESSION['id_usuario'])) {
    $rol = $_SESSION['rol'];
    $redirects = [
        'admin'   => URL_BASE . 'admin/index.php',
        'profesor'=> URL_BASE . 'profesor/index.php',
        'alumno'  => URL_BASE . 'alumno/index.php'
    ];
    header('Location: ' . ($redirects[$rol] ?? URL_BASE . 'auth/login.php'));
} else {
    header('Location: ' . URL_BASE . 'auth/login.php');
}
exit;
