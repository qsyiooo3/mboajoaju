<?php
// auth/login.php — Mboa' Joaju v2.0
// Login con diseño de Barreto + procesamiento funcional
require_once __DIR__ . '/../config/config.php';

// Si ya está autenticado (y no es POST), redirigir según rol
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_SESSION['usuario_id']) && isset($_SESSION['rol'])) {
    $destino = match ($_SESSION['rol']) {
        'admin'    => URL_BASE . 'admin/index.php',
        'profesor' => URL_BASE . 'profesor/index.php',
        'alumno'   => URL_BASE . 'alumno/index.php',
        default    => URL_BASE . 'index.php'
    };
    header("Location: $destino");
    exit();
}

$error = '';
$correo = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Error de seguridad. Recargue la página.';
    } else {
        $correo = sanitizeInput($_POST['correo'] ?? '');
        $contrasena = $_POST['contrasena'] ?? '';

        if (empty($correo) || empty($contrasena)) {
            $error = 'Por favor, completa todos los campos.';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE correo = ? AND activo = 1");
                $stmt->execute([$correo]);
                $usuario = $stmt->fetch();

                if ($usuario && password_verify($contrasena, $usuario['contrasena'])) {
                    // Limpiar sesión vieja
                    session_unset();
                    session_regenerate_id(true);

                    // Session vars for our code
                    $_SESSION['usuario_id'] = (int)$usuario['id'];
                    $_SESSION['nombre_completo'] = $usuario['nombre_completo'];
                    // Session vars for team's code (also used by navbars)
                    $_SESSION['id_usuario'] = (int)$usuario['id'];
                    $_SESSION['nombre'] = $usuario['nombre_completo'];
                    // Common vars
                    $_SESSION['rol'] = $usuario['rol'];
                    $_SESSION['correo'] = $usuario['correo'];
                    $_SESSION['avatar'] = $usuario['avatar'] ?? null;
                    $_SESSION['ultimo_acceso'] = time();

                    logActivity($pdo, $usuario['id'], 'login', 'Inicio de sesión exitoso');

                    $stmt = $pdo->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?");
                    $stmt->execute([$usuario['id']]);

                    $redirect = [
                        'admin'    => URL_BASE . 'admin/index.php',
                        'profesor' => URL_BASE . 'profesor/index.php',
                        'alumno'   => URL_BASE . 'alumno/index.php'
                    ];
                    header('Location: ' . ($redirect[$usuario['rol']] ?? URL_BASE . 'alumno/index.php'));
                    exit;
                } else {
                    $error = 'Usuario o contraseña incorrectos.';
                    logActivity($pdo, null, 'login_failed', "Intento fallido para: $correo");
                }
            } catch (PDOException $e) {
                error_log("Error en login: " . $e->getMessage());
                $error = 'Error al procesar la solicitud. Intenta nuevamente.';
            }
        }
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= URL_BASE ?>assets/css/style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="login-page">

<div class="login-box">
    <!-- LOGO -->
    <div class="login-logo">
        <img src="<?= URL_BASE ?>assets/images/logos/logo.png"
             alt="<?= APP_NAME ?> — CTA Augusto Roa Bastos"
             class="login-logo-img"
             width="300" height="300"
             onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
        <span class="logo-icon-fallback" style="display:none;font-size:4rem;">🌾</span>
        <h1><?= APP_NAME ?></h1>
        <p>Colegio Técnico Agropecuario "Augusto Roa Bastos"</p>
    </div>

    <!-- ERROR -->
    <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-triangle"></i>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- FORMULARIO -->
    <form method="POST" action="" class="login-form" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

        <div class="form-group">
            <label for="correo">
                <i class="fas fa-user"></i> Usuario / Correo
            </label>
            <input type="text" id="correo" name="correo"
                   value="<?= htmlspecialchars($correo) ?>"
                   placeholder="Ej: benitez.maria12345"
                   maxlength="100" required autofocus autocomplete="username">
        </div>

        <div class="form-group">
            <label for="contrasena">
                <i class="fas fa-lock"></i> Contraseña
            </label>
            <div class="password-input">
                <input type="password" id="contrasena" name="contrasena"
                       placeholder="••••••••" maxlength="72" required autocomplete="current-password">
                <button type="button" class="toggle-password" onclick="togglePassword()">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn-primary btn-block">
            <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
        </button>
    </form>

    <!-- FOOTER -->
    <div class="login-footer">
        <p>ℹ️ Usa las credenciales asignadas por el administrador</p>
        <p class="version">Mboa' Joaju v<?= APP_VERSION ?> — Sistema Educativo Local</p>
    </div>
</div>

<script>
function togglePassword() {
    const input = document.getElementById('contrasena');
    const icon = document.querySelector('.toggle-password i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}
</script>

</body>
</html>
