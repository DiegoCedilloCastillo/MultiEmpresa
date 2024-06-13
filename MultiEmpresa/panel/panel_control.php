<?php
// Iniciar una nueva sesión o reanudar la existente si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario_id'])) {
    // Redirigir al formulario de inicio de sesión si no está autenticado
    header('Location: ../index.php');
    exit();
}

// Obtener el rol del usuario de la sesión
$rol = $_SESSION['rol'];

// Redirigir al panel correspondiente según el rol del usuario
switch ($rol) {
    case 'administrador':
    case 'administrador_base': // Redirigir al panel de administración para roles 'administrador' y 'administrador_base'
        header('Location: ../panel/panel_admin.php');
        break;
    case 'gerente': // Redirigir al panel de gerente
        header('Location: ../panel/panel_gerente.php');
        break;
    case 'usuario': // Redirigir al panel de usuario
        header('Location: ../panel/panel_usuario.php');
        break;
    case 'archon': // Redirigir al panel de archon
        header('Location: ../panel/panel_archon.php');
        break;
    default:
        // Redirigir al formulario de inicio de sesión si el rol no es reconocido
        header('Location: ../index.php');
        break;
}
exit();