<?php
// ============================================================
// INCLUDES / NAVBAR_PROFESOR.PHP — Mboa' Joaju v2.0
// Barra de navegación para Profesor
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
        <p>Profesor</p>
    </div>

    <ul class="sidebar-menu">
        <li>
            <a href="<?= BASE_URL ?>profesor/index.php"
               class="<?= ($seccion ?? '') === 'dashboard' ? 'active' : '' ?>">
                <span class="menu-icon">📊</span>
                Dashboard
            </a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>profesor/canales.php"
               class="<?= ($seccion ?? '') === 'canales' ? 'active' : '' ?>">
                <span class="menu-icon">📺</span>
                Mis Canales
            </a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>profesor/publicaciones.php"
               class="<?= ($seccion ?? '') === 'publicaciones' ? 'active' : '' ?>">
                <span class="menu-icon">📝</span>
                Publicaciones
            </a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>profesor/entregas.php"
               class="<?= ($seccion ?? '') === 'entregas' ? 'active' : '' ?>">
                <span class="menu-icon">📥</span>
                Entregas
            </a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>profesor/alumnos.php"
               class="<?= ($seccion ?? '') === 'alumnos' ? 'active' : '' ?>">
                <span class="menu-icon">🎓</span>
                Alumnos
            </a>
        </li>
    </ul>

    <div class="sidebar-footer">
        <div class="user-info">
            <span class="user-avatar"><?= substr($_SESSION['nombre'] ?? 'P', 0, 1) ?></span>
            <span class="user-name"><?= htmlspecialchars($_SESSION['nombre'] ?? 'Profesor', ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <a href="<?= BASE_URL ?>auth/logout.php" class="btn-logout">
            🚪 Cerrar Sesión
        </a>
    </div>
</div>

<!-- Overlay para móvil -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>