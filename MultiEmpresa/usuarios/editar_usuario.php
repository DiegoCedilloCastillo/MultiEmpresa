<?php
// Iniciar una nueva sesión o reanudar la existente
session_start();
// Incluir configuración de la base de datos y funciones necesarias
require_once '../includes/config.php';
require_once '../includes/funciones.php';
require_once '../notificaciones/funciones_notificaciones.php';

// Verificar si la sesión está iniciada y si el usuario tiene el rol adecuado
verificarSesionIniciada(['administrador', 'gerente', 'archon', 'administrador_base']);

// Obtener el ID del usuario a editar desde la URL
$id_usuario = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener los datos del formulario y escaparlos para evitar inyecciones SQL
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $apellido = $conn->real_escape_string($_POST['apellido']);
    $email = $conn->real_escape_string($_POST['email']);
    $rol = $conn->real_escape_string($_POST['rol']);

    // Verificar que solo los Archons puedan cambiar el rol a 'administrador_base'
    if ($rol == 'administrador_base' && $_SESSION['rol'] != 'archon') {
        echo "<script>alert('Solo los Archons pueden asignar el rol de Administrador Base.'); window.location.href = 'usuarios.php';</script>";
        exit();
    }

    // Obtener los datos actuales del usuario antes de actualizar
    $sql_usuario_actual = "SELECT nombre, rol FROM usuarios WHERE id = ?";
    $stmt_usuario_actual = $conn->prepare($sql_usuario_actual);
    $stmt_usuario_actual->bind_param("i", $id_usuario);
    $stmt_usuario_actual->execute();
    $result_usuario_actual = $stmt_usuario_actual->get_result();
    $usuario_actual = $result_usuario_actual->fetch_assoc();

    $nombre_anterior = $usuario_actual['nombre'];
    $rol_anterior = $usuario_actual['rol'];

    // Actualizar los datos del usuario en la base de datos
    $sql_update = "UPDATE usuarios SET nombre = ?, apellido = ?, email = ?, rol = ? WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("ssssi", $nombre, $apellido, $email, $rol, $id_usuario);

    if ($stmt_update->execute()) {
        // Obtener el nombre del usuario que realiza la acción
        $usuario_actual_nombre = obtenerNombreUsuario($conn, $_SESSION['usuario_id']);
        
        // Crear el mensaje de notificación
        $mensaje = "{$usuario_actual_nombre} actualizó los datos de {$rol_anterior}: {$nombre_anterior}";
        
        // Crear notificación para admins y gerentes
        crearNotificacionAdminsYGerentes($conn, $_SESSION['id_empresa'], $mensaje, $_SESSION['usuario_id']);

        // Redirigir después de la actualización exitosa
        header("Location: usuarios.php");
        exit();
    } else {
        echo "<script>alert('Error al actualizar el usuario.');</script>";
    }
}

// Obtener datos del usuario desde la base de datos
$sql_usuario = "SELECT * FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql_usuario);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result_usuario = $stmt->get_result();
$usuario = $result_usuario->fetch_assoc();

if (!$usuario) {
    echo "<script>alert('Usuario no encontrado.'); window.location.href = 'usuarios.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <!-- Establecer la configuración de la ventana gráfica para asegurar que la página se vea bien en dispositivos móviles -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Título de la página -->
    <title>Editar Usuario</title>
    <!-- Enlace al archivo de estilos CSS -->
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
    <section class="form-container">
        <h2>Editar Usuario</h2>
        <!-- Formulario para editar el usuario -->
        <form action="editar_usuario.php?id=<?= htmlspecialchars($id_usuario) ?>" method="POST">
            <label for="nombre">Nombre</label>
            <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($usuario['nombre']) ?>" required>
            <label for="apellido">Apellido</label>
            <input type="text" id="apellido" name="apellido" value="<?= htmlspecialchars($usuario['apellido']) ?>" required>
            <label for="email">Correo Electrónico</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($usuario['email']) ?>" required>
            <label for="rol">Rol</label>
            <select id="rol" name="rol" required>
                <?php if ($_SESSION['rol'] == 'archon'): ?>
                    <option value="administrador" <?= $usuario['rol'] == 'administrador' ? 'selected' : '' ?>>Administrador</option>
                    <option value="administrador_base" <?= $usuario['rol'] == 'administrador_base' ? 'selected' : '' ?>>Administrador Base</option>
                <?php elseif ($_SESSION['rol'] == 'administrador' || $_SESSION['rol'] == 'administrador_base'): ?>
                    <option value="administrador" <?= $usuario['rol'] == 'administrador' ? 'selected' : '' ?>>Administrador</option>
                    <option value="gerente" <?= $usuario['rol'] == 'gerente' ? 'selected' : '' ?>>Gerente</option>
                    <option value="usuario" <?= $usuario['rol'] == 'usuario' ? 'selected' : '' ?>>Usuario</option>
                <?php else: ?>
                    <option value="gerente" <?= $usuario['rol'] == 'gerente' ? 'selected' : '' ?>>Gerente</option>
                    <option value="usuario" <?= $usuario['rol'] == 'usuario' ? 'selected' : '' ?>>Usuario</option>
                <?php endif; ?>
            </select>
            <button type="submit">Actualizar Usuario</button>
        </form>
    </section>
</body>
</html>