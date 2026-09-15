<?php
// ============================================================
// INCLUDES / NAVBAR_ADMIN.PHP — Mboa' Joaju v2.0
// Barra de navegación para Administrador
// ============================================================

if (!defined('BASE_URL')) {
    define('BASE_URL', URL_BASE);
}
?>
<!-- ══════════════════════════════════════════════════════════
     SIDEBAR ADMIN
     ══════════════════════════════════════════════════════════ -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <img src="<?= BASE_URL ?>assets/images/logos/logo-blanco.png"
             alt="Mboa' Joaju"
             class="sidebar-logo"
             width="120"
             height="120"
             onerror="this.src='<?= BASE_URL ?>assets/images/logos/logo.png'; this.onerror=null;">
        <h2>Mboa' Joaju</h2>
        <p>Administrador</p>
    </div>

    <ul class="sidebar-menu">
        <li>
            <a href="<?= BASE_URL ?>admin/index.php"
               class="<?= ($seccion ?? '') === 'dashboard' ? 'active' : '' ?>">
                <span class="menu-icon">📊</span>
                Dashboard
            </a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>admin/usuarios.php"
               class="<?= ($seccion ?? '') === 'usuarios' ? 'active' : '' ?>">
                <span class="menu-icon">👥</span>
                Usuarios
            </a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>admin/alumnos.php"
               class="<?= ($seccion ?? '') === 'alumnos' ? 'active' : '' ?>">
                <span class="menu-icon">🎓</span>
                Alumnos
            </a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>admin/profesores.php"
               class="<?= ($seccion ?? '') === 'profesores' ? 'active' : '' ?>">
                <span class="menu-icon">👨‍🏫</span>
                Profesores
            </a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>admin/canales.php"
               class="<?= ($seccion ?? '') === 'canales' ? 'active' : '' ?>">
                <span class="menu-icon">📺</span>
                Canales
            </a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>admin/configuracion.php"
               class="<?= ($seccion ?? '') === 'configuracion' ? 'active' : '' ?>">
                <span class="menu-icon">⚙️</span>
                Configuración
            </a>
        </li>
    </ul>

    <div class="sidebar-footer">
        <div class="user-info">
            <span class="user-avatar"><?= substr($_SESSION['nombre'] ?? 'A', 0, 1) ?></span>
            <span class="user-name"><?= htmlspecialchars($_SESSION['nombre'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <a href="<?= BASE_URL ?>auth/logout.php" class="btn-logout">
            🚪 Cerrar Sesión
        </a>
    </div>
</div>

<!-- Overlay para móvil (cierra el sidebar al tocar fuera) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>