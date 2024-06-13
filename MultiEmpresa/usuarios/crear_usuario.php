<?php
// Iniciar una nueva sesión o reanudar la existente
session_start();
// Incluir funciones necesarias para verificar la sesión y el rol del usuario
require_once '../includes/funciones.php';
require_once '../notificaciones/funciones_notificaciones.php';

// Verificar si la sesión está iniciada y si el usuario tiene el rol adecuado
verificarSesionIniciada(['administrador', 'gerente', 'archon', 'administrador_base']);

// Incluir configuración de la base de datos
include '../includes/config.php';

// Verificar si el parámetro 'archon' está presente en la URL
$es_archon = isset($_GET['archon']) && $_GET['archon'] == 1;

// Verificar si la solicitud es POST, lo que indica que el formulario ha sido enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Escapar los datos del formulario para evitar inyecciones SQL
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $apellido = $conn->real_escape_string($_POST['apellido']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $conn->real_escape_string($_POST['password']);
    $confirm_password = $conn->real_escape_string($_POST['confirm_password']);
    $rol = $conn->real_escape_string($_POST['rol']);

    // Verificar que las contraseñas coincidan
    if ($password !== $confirm_password) {
        $error = "Las contraseñas no coinciden.";
    } else {
        // Hashear la contraseña
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Generar ID general
        $id_general = generar_id($conn, 'usuario');

        // Insertar en la tabla Usuarios
        if ($es_archon) {
            // Archon crea usuario sin empresa asociada
            $sql = "INSERT INTO usuarios (id_general, nombre, apellido, email, password, rol) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isssss", $id_general, $nombre, $apellido, $email, $hashed_password, $rol);
        } else {
            // Administrador o Gerente crea usuario con empresa asociada
            $id_empresa = $_SESSION['id_empresa'];
            $sql = "INSERT INTO usuarios (id_general, nombre, apellido, email, password, rol, id_empresa) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isssssi", $id_general, $nombre, $apellido, $email, $hashed_password, $rol, $id_empresa);
        }

        if ($stmt->execute()) {
            // Crear la notificación
            $usuario_actual = $_SESSION['usuario_id'];
            $nombre_usuario_actual = $_SESSION['nombre'];
            $accion = "{$nombre_usuario_actual} añadió un nuevo {$rol}: {$nombre}";
            if ($es_archon) {
                crearNotificacion($conn, $accion, $usuario_actual);
            } else {
                crearNotificacionAdminsYGerentes($conn, $id_empresa, $accion, $usuario_actual);
            }

            // Establecer un mensaje de éxito en la sesión y redirigir a la página de usuarios
            $_SESSION['mensaje'] = "Usuario creado exitosamente.";
            header("Location: usuarios.php");
            exit();
        } else {
            // Manejar error en la inserción del usuario
            $error = "Error: " . $stmt->error;
            // Si falla la inserción en Usuarios, eliminar la entrada en IDs
            $conn->query("DELETE FROM ids WHERE id = $id_general");
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <!-- Establecer la configuración de la ventana gráfica para asegurar que la página se vea bien en dispositivos móviles -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Título de la página -->
    <title>Crear Usuario</title>
    <!-- Enlace al archivo de estilos CSS -->
    <link rel="stylesheet" href="../css/estilos.css">
    <!-- Enlace al archivo de scripts JavaScript -->
    <script src="../js/scripts.js"></script>
</head>
<body>
    <!-- Incluir el encabezado común a todas las páginas -->
    <?php include '../includes/encabezado.php'; ?>

    <section class="form-container">
        <h2>Crear Usuario</h2>
        <!-- Mostrar el mensaje de error si existe -->
        <?php if (isset($error)) : ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <!-- Formulario para crear el usuario -->
        <form id="formularioRegistro" action="crear_usuario.php<?php if ($es_archon) echo '?archon=1'; ?>" method="POST">
            <label for="nombre">Nombre</label>
            <input type="text" id="nombre" name="nombre" required>
            <label for="apellido">Apellido</label>
            <input type="text" id="apellido" name="apellido" required>
            <label for="email">Correo Electrónico</label>
            <input type="email" id="email" name="email" required>
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" required>
            <label for="confirm_password">Confirmar Contraseña</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
            <label for="rol">Rol</label>
            <select id="rol" name="rol" required>
                <?php if ($_SESSION['rol'] == 'archon'): ?>
                    <option value="administrador">Administrador</option>
                    <option value="administrador_base">Administrador Base</option>
                <?php elseif ($_SESSION['rol'] == 'administrador' || $_SESSION['rol'] == 'administrador_base'): ?>
                    <option value="administrador">Administrador</option>
                    <option value="gerente">Gerente</option>
                    <option value="usuario">Usuario</option>
                <?php else: ?>
                    <option value="gerente">Gerente</option>
                    <option value="usuario">Usuario</option>
                <?php endif; ?>
            </select>
            <button type="submit">Crear Usuario</button>
        </form>
    </section>

    <!-- Incluir el pie de página común a todas las páginas -->
    <?php include '../includes/pie_pagina.php'; ?>
</body>
</html>