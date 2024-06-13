<?php
// Inicia una nueva sesión o reanuda la existente
session_start();

// Verifica si el usuario ya ha iniciado sesión comprobando la existencia del identificador de usuario en la sesión
if (isset($_SESSION['usuario_id'])) {
    // Si el usuario ya ha iniciado sesión, redirige al panel de control
    header('Location: panel/panel_control.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <!-- Configuración de la ventana gráfica para asegurar una correcta visualización en dispositivos móviles -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Título de la página -->
    <title>Inicio de Sesión</title>
    <!-- Enlace al archivo de estilos CSS -->
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
    <!-- Incluye el archivo del encabezado común a todas las páginas -->
    <?php include 'includes/encabezado.php'; ?>

    <!-- Sección del formulario de inicio de sesión -->
    <section class="form-container">
        <h2>Inicio de Sesión</h2>
        <!-- Si existe un mensaje de error en la sesión, se muestra y luego se elimina para evitar mostrarlo nuevamente -->
        <?php if (isset($_SESSION['error'])) : ?>
            <p class="error"><?= htmlspecialchars($_SESSION['error']) ?></p>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        <!-- Formulario para el inicio de sesión -->
        <form id="formularioInicioSesion" action="auth/login.php" method="POST">
            <label for="email">Correo Electrónico</label>
            <!-- Campo de entrada para el correo electrónico del usuario -->
            <input type="email" id="email" name="email" required>
            <label for="password">Contraseña</label>
            <!-- Campo de entrada para la contraseña del usuario -->
            <input type="password" id="password" name="password" required>
            <!-- Botón para enviar el formulario de inicio de sesión -->
            <button type="submit">Iniciar Sesión</button>
        </form>
    </section>

    <!-- Incluye el archivo del pie de página común a todas las páginas -->
    <?php include 'includes/pie_pagina.php'; ?>
</body>
</html>