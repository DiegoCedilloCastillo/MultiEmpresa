<?php
// Iniciar la sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir el archivo de configuración para conectar a la base de datos
include dirname(__FILE__) . '/config.php';

// Inicializar el contador de notificaciones no leídas
$no_leidas = 0;
if (isset($_SESSION['usuario_id'])) {
    $id_usuario = $_SESSION['usuario_id'];

    // Consulta para contar las notificaciones no leídas del usuario
    $sql_notificaciones = "SELECT COUNT(*) AS no_leidas FROM Notificaciones WHERE id_usuario = ? AND leida = FALSE";
    if ($stmt_notificaciones = $conn->prepare($sql_notificaciones)) {
        $stmt_notificaciones->bind_param("i", $id_usuario);
        $stmt_notificaciones->execute();
        $result_notificaciones = $stmt_notificaciones->get_result();

        if ($result_notificaciones) {
            $row_notificaciones = $result_notificaciones->fetch_assoc();
            $no_leidas = $row_notificaciones['no_leidas'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MultiEmpresa</title>
    <link rel="stylesheet" href="/MultiEmpresa/css/estilos.css">
</head>
<body>
    <header>
        <div class="empresa">
            <?php
            // Mostrar el nombre de la empresa asociada al usuario
            if (isset($_SESSION['id_empresa'])) {
                $id_empresa = $_SESSION['id_empresa'];
                $sql_empresa = "SELECT nombre FROM Empresas WHERE id = ?";
                if ($stmt_empresa = $conn->prepare($sql_empresa)) {
                    $stmt_empresa->bind_param("i", $id_empresa);
                    $stmt_empresa->execute();
                    $result_empresa = $stmt_empresa->get_result();
                    if ($result_empresa->num_rows > 0) {
                        $empresa = $result_empresa->fetch_assoc();
                        echo htmlspecialchars($empresa['nombre']);
                    }
                }
            }
            ?>
        </div>
        <nav>
            <ul>
                <?php if (isset($_SESSION['usuario_id'])): ?>
                    <li>
                        <a href="<?php
                            // Enlace al panel de control basado en el rol del usuario
                            switch ($_SESSION['rol']) {
                                case 'archon':
                                    echo '/MultiEmpresa/panel/panel_archon.php';
                                    break;
                                case 'administrador':
                                case 'administrador_base':
                                    echo '/MultiEmpresa/panel/panel_admin.php';
                                    break;
                                case 'gerente':
                                    echo '/MultiEmpresa/panel/panel_gerente.php';
                                    break;
                                case 'usuario':
                                    echo '/MultiEmpresa/panel/panel_usuario.php';
                                    break;
                                default:
                                    echo '/MultiEmpresa/auth/login.php';
                                    break;
                            }
                        ?>">Panel de Control</a>
                    </li>
                    <?php if (in_array($_SESSION['rol'], ['administrador', 'administrador_base', 'gerente'])): ?>
                        <li><a href="/MultiEmpresa/includes/historial_actividades.php">Historial de Actividades</a></li>
                    <?php endif; ?>
                    <li><a href="/MultiEmpresa/notificaciones/notificaciones.php" class="<?php echo $no_leidas > 0 ? 'notificaciones-unread' : ''; ?>">Notificaciones<?php echo $no_leidas > 0 ? " ($no_leidas)" : ''; ?></a></li>
                    <li><a href="/MultiEmpresa/auth/cerrar_sesion.php">Cerrar Sesión</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
</body>
</html>