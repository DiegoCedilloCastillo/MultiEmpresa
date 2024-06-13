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
    $nombre_empresa = $conn->real_escape_string($_POST['nombre_empresa']);
    $direccion = $conn->real_escape_string($_POST['direccion']);
    $telefono = $conn->real_escape_string($_POST['telefono']);
    $email_empresa = $conn->real_escape_string($_POST['email']);

    // Insertar en la tabla IDs para la empresa y generar un ID general único
    $id_empresa_general = generar_id($conn, 'empresa');

    if ($id_empresa_general) {
        // Insertar los datos de la empresa en la tabla Empresas usando el ID general obtenido
        $sql_empresa = "INSERT INTO empresas (id_general, nombre, direccion, telefono, email) 
                        VALUES (?, ?, ?, ?, ?)";
        $stmt_empresa = $conn->prepare($sql_empresa);
        $stmt_empresa->bind_param("issss", $id_empresa_general, $nombre_empresa, $direccion, $telefono, $email_empresa);

        if ($stmt_empresa->execute()) {
            // Obtener el ID de la empresa insertada
            $id_empresa = $stmt_empresa->insert_id;

            // Escapar los datos del administrador del formulario
            $nombre_admin = $conn->real_escape_string($_POST['nombre_admin']);
            $apellido_admin = $conn->real_escape_string($_POST['apellido_admin']);
            $email_admin = $conn->real_escape_string($_POST['email_admin']);
            // Encriptar la contraseña del administrador usando bcrypt
            $password_admin = password_hash($conn->real_escape_string($_POST['password_admin']), PASSWORD_BCRYPT);

            // Insertar en la tabla IDs para el administrador y generar un ID general único
            $id_admin_general = generar_id($conn, 'usuario');

            if ($id_admin_general) {
                // Insertar los datos del administrador en la tabla Usuarios usando el ID general obtenido
                $sql_admin = "INSERT INTO usuarios (id_general, nombre, apellido, email, password, rol, id_empresa) 
                              VALUES (?, ?, ?, ?, ?, 'administrador_base', ?)";
                $stmt_admin = $conn->prepare($sql_admin);
                $stmt_admin->bind_param("issssi", $id_admin_general, $nombre_admin, $apellido_admin, $email_admin, $password_admin, $id_empresa);

                if ($stmt_admin->execute()) {
                    // Crear la notificación para los Archons
                    $usuario_actual = $_SESSION['usuario_id'];
                    $nombre_usuario_actual = obtenerNombreArchon($conn, $usuario_actual);
                    $id_admin_creado = $stmt_admin->insert_id;
                    $accion = "$nombre_usuario_actual ha registrado una nueva empresa: $nombre_empresa, con el administrador: $nombre_admin";
                    
                    // Notificar a todos los Archons excepto al que creó la empresa
                    crearNotificacionArchons($conn, $accion, $usuario_actual, $id_empresa);

                    // Establecer un mensaje de éxito en la sesión y redirigir a la página de empresas
                    $_SESSION['mensaje'] = "Empresa y administrador creados exitosamente.";
                    header("Location: empresas.php");
                    exit();
                } else {
                    // Manejar error en la inserción del administrador
                    $error = "Error al crear el administrador: " . $stmt_admin->error;

                    // Si falla la inserción en Usuarios, eliminar las entradas en IDs y Empresas
                    $conn->query("DELETE FROM empresas WHERE id = $id_empresa");
                    $conn->query("DELETE FROM ids WHERE id = $id_empresa_general OR id = $id_admin_general");
                }
            } else {
                // Manejar error en la generación del ID para el administrador
                $error = "Error al crear el ID del administrador.";

                // Si falla la inserción en IDs para el administrador, eliminar la entrada en Empresas y IDs
                $conn->query("DELETE FROM empresas WHERE id = $id_empresa");
                $conn->query("DELETE FROM ids WHERE id = $id_empresa_general");
            }
        } else {
            // Manejar error en la inserción de la empresa
            $error = "Error al crear la empresa: " . $stmt_empresa->error;

            // Si falla la inserción en Empresas, eliminar la entrada en IDs
            $conn->query("DELETE FROM ids WHERE id = $id_empresa_general");
        }
    } else {
        // Manejar error en la generación del ID para la empresa
        $error = "Error al crear el ID de la empresa.";
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
    <title>Crear Empresa</title>
    <!-- Enlace al archivo de estilos CSS -->
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
    <!-- Incluir el encabezado común a todas las páginas -->
    <?php include '../includes/encabezado.php'; ?>

    <section class="form-container">
        <h2>Crear Empresa</h2>
        <!-- Mostrar el mensaje de error si existe -->
        <?php if (isset($error)) : ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <!-- Formulario para crear la empresa -->
        <form action="crear_empresa.php" method="POST">
            <label for="nombre_empresa">Nombre de la Empresa</label>
            <input type="text" id="nombre_empresa" name="nombre_empresa" required>
            <label for="direccion">Dirección</label>
            <input type="text" id="direccion" name="direccion" required>
            <label for="telefono">Teléfono</label>
            <input type="text" id="telefono" name="telefono" required>
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>

            <h3>Crear Administrador para la Empresa</h3>
            <label for="nombre_admin">Nombre del Administrador</label>
            <input type="text" id="nombre_admin" name="nombre_admin" required>
            <label for="apellido_admin">Apellido del Administrador</label>
            <input type="text" id="apellido_admin" name="apellido_admin" required>
            <label for="email_admin">Email del Administrador</label>
            <input type="email" id="email_admin" name="email_admin" required>
            <label for="password_admin">Contraseña del Administrador</label>
            <input type="password" id="password_admin" name="password_admin" required>
            
            <button type="submit">Crear Empresa y Administrador</button>
        </form>
    </section>

    <!-- Incluir el pie de página común a todas las páginas -->
    <?php include '../includes/pie_pagina.php'; ?>
</body>
</html>