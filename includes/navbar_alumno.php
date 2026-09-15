<?php
// ============================================================
// INCLUDES / NAVBAR_ALUMNO.PHP — Mboa' Joaju v2.0
// Barra de navegación para Alumno
// ============================================================

if (!defined('BASE_URL')) {
    define('BASE_URL', URL_BASE);
}
?>
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <img src="<?= BASE_URL ?>assets/images/logos/logo-blanco.png"
             alt="Mboa' Joaju"
             class="sidebar-logo"
             width="120"
             height="120"
             onerror="this.src='<?= BASE_URL ?>assets/images/logos/logo.png'; this.onerror=null;">
        <h2>Mboa' Joaju</h2>
        <p>Alumno</p>
    </div>

    <ul class="sidebar-menu">
        <li>
            <a href="<?= BASE_URL ?>alumno/index.php"
               class="<?= ($seccion ?? '') === 'dashboard' ? 'active' : '' ?>">
                <span class="menu-icon">📊</span>
                Dashboard
            </a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>alumno/canales.php"
               class="<?= ($seccion ?? '') === 'canales' ? 'active' : '' ?>">
                <span class="menu-icon">📺</span>
                Mis Canales
            </a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>alumno/publicaciones.php"
               class="<?= ($seccion ?? '') === 'publicaciones' ? 'active' : '' ?>">
                <span class="menu-icon">📝</span>
                Publicaciones
            </a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>alumno/entregas.php"
               class="<?= ($seccion ?? '') === 'entregas' ? 'active' : '' ?>">
                <span class="menu-icon">📥</span>
                Mis Entregas
            </a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>alumno/calificaciones.php"
               class="<?= ($seccion ?? '') === 'calificaciones' ? 'active' : '' ?>">
                <span class="menu-icon">📊</span>
                Calificaciones
            </a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>alumno/perfil.php"
               class="<?= ($seccion ?? '') === 'perfil' ? 'active' : '' ?>">
                <span class="menu-icon">👤</span>
                Mi Perfil
            </a>
        </li>
    </ul>

    <div class="sidebar-footer">
        <div class="user-info">
            <span class="user-avatar"><?= substr($_SESSION['nombre'] ?? 'A', 0, 1) ?></span>
            <span class="user-name"><?= htmlspecialchars($_SESSION['nombre'] ?? 'Alumno', ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <a href="<?= BASE_URL ?>auth/logout.php" class="btn-logout">
            🚪 Cerrar Sesión
        </a>
    </div>
</div>

<!-- Overlay para móvil -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>