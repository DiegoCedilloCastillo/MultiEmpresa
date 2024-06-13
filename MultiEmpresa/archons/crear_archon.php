<?php
// Iniciar una nueva sesión o reanudar la existente
session_start();
// Incluir funciones necesarias para verificar la sesión y el rol del usuario
require_once '../includes/funciones.php';
require_once '../notificaciones/funciones_notificaciones.php';

// Verificar si la sesión está iniciada y si el usuario tiene el rol adecuado (archon)
verificarSesionIniciada(['archon']);

// Incluir configuración de la base de datos
include '../includes/config.php';

// Verificar si la solicitud es POST, lo que indica que el formulario ha sido enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Escapar los datos del formulario para evitar inyecciones SQL
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $apellido = $conn->real_escape_string($_POST['apellido']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $conn->real_escape_string($_POST['password']);
    $confirm_password = $conn->real_escape_string($_POST['confirm_password']);

    // Verificar que las contraseñas coincidan
    if ($password !== $confirm_password) {
        $error = "Las contraseñas no coinciden.";
    } else {
        // Hashear la contraseña
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Generar ID general
        $id_general = generar_id($conn, 'archon');

        // Insertar los datos en la tabla Archons
        $sql = "INSERT INTO archons (id_general, nombre, apellido, email, password) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issss", $id_general, $nombre, $apellido, $email, $hashed_password);

        if ($stmt->execute()) {
            // Crear la notificación
            $usuario_actual = $_SESSION['usuario_id'];
            $nombre_usuario_actual = obtenerNombreArchon($conn, $usuario_actual);
            $id_archon_creado = $stmt->insert_id;
            $accion = "{$nombre_usuario_actual} añadió un nuevo archon: {$nombre}";
            
            // Notificar a todos los Archons, excluyendo el Archon recién creado
            crearNotificacionArchons($conn, $accion, $usuario_actual);

            // Notificar al Archon recién creado
            crearNotificacionUsuario($conn, $id_archon_creado, $accion);

            // Establecer un mensaje de éxito en la sesión y redirigir a la página de Archons
            $_SESSION['mensaje'] = "Archon creado exitosamente.";
            header("Location: archons.php");
            exit();
        } else {
            // Manejar error en la inserción del Archon
            $error = "Error: " . $stmt->error;
            // Si falla la inserción en Archons, eliminar la entrada en IDs
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
    <title>Crear Archon</title>
    <!-- Enlace al archivo de estilos CSS -->
    <link rel="stylesheet" href="../css/estilos.css">
    <!-- Enlace al archivo de scripts JavaScript -->
    <script src="../js/scripts.js"></script>
</head>
<body>
    <!-- Incluir el encabezado común a todas las páginas -->
    <?php include '../includes/encabezado.php'; ?>

    <section class="form-container">
        <h2>Crear Archon</h2>
        <!-- Mostrar el mensaje de error si existe -->
        <?php if (isset($error)) : ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <!-- Formulario para crear el Archon -->
        <form id="formularioRegistro" action="crear_archon.php" method="POST">
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
            <button type="submit">Crear Archon</button>
        </form>
    </section>

    <!-- Incluir el pie de página común a todas las páginas -->
    <?php include '../includes/pie_pagina.php'; ?>
</body>
</html>