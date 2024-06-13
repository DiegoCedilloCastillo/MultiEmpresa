<?php
// Iniciar una nueva sesión o reanudar la existente
session_start();
// Incluir funciones necesarias para verificar la sesión y el rol del usuario
require_once '../includes/funciones.php';
require_once '../notificaciones/funciones_notificaciones.php';

// Verificar si la sesión está iniciada y si el usuario tiene uno de los roles permitidos
verificarSesionIniciada(['administrador', 'gerente', 'archon', 'administrador_base']);

// Incluir configuración de la base de datos
include '../includes/config.php';

// Verificar si la solicitud es POST, lo que indica que el formulario ha sido enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Escapar los datos del formulario para evitar inyecciones SQL
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $tipo = $conn->real_escape_string($_POST['tipo']);
    $empresa = $conn->real_escape_string($_POST['empresa']);
    $telefono = $conn->real_escape_string($_POST['telefono']);
    $email = $conn->real_escape_string($_POST['email']);

    // Generar ID general
    $id_general = generar_id($conn, 'contacto');

    // Insertar los datos del contacto en la tabla Contactos
    $id_empresa = $_SESSION['id_empresa'];
    $sql = "INSERT INTO contactos (id_general, nombre, tipo, empresa, telefono, email, id_empresa) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssssi", $id_general, $nombre, $tipo, $empresa, $telefono, $email, $id_empresa);

    if ($stmt->execute()) {
        // Crear la notificación
        $usuario_actual = $_SESSION['usuario_id'];
        $nombre_usuario_actual = $_SESSION['nombre'];
        $accion = "{$nombre_usuario_actual} añadió un nuevo contacto: {$nombre} ({$tipo})";
        crearNotificacionAdminsYGerentes($conn, $id_empresa, $accion, $usuario_actual);

        // Establecer un mensaje de éxito en la sesión y redirigir a la página de contactos
        $_SESSION['mensaje'] = "Contacto creado exitosamente.";
        header("Location: contactos.php");
        exit();
    } else {
        // Manejar error en la inserción del contacto
        $error = "Error: " . $stmt->error;
        // Si falla la inserción en Contactos, eliminar la entrada en IDs
        $conn->query("DELETE FROM ids WHERE id = $id_general");
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
    <title>Crear Contacto</title>
    <!-- Enlace al archivo de estilos CSS -->
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
    <!-- Incluir el encabezado común a todas las páginas -->
    <?php include '../includes/encabezado.php'; ?>

    <section class="form-container">
        <h2>Crear Contacto</h2>
        <!-- Mostrar el mensaje de error si existe -->
        <?php if (isset($error)) : ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <!-- Formulario para crear el contacto -->
        <form action="crear_contacto.php" method="POST">
            <label for="nombre">Nombre</label>
            <input type="text" id="nombre" name="nombre" required>
            <label for="tipo">Tipo</label>
            <select id="tipo" name="tipo" required>
                <option value="proveedor">Proveedor</option>
                <option value="cliente">Cliente</option>
                <option value="socio">Socio</option>
            </select>
            <label for="empresa">Empresa</label>
            <input type="text" id="empresa" name="empresa" required>
            <label for="telefono">Teléfono</label>
            <input type="text" id="telefono" name="telefono" required>
            <label for="email">Correo Electrónico</label>
            <input type="email" id="email" name="email" required>
            <button type="submit">Crear Contacto</button>
        </form>
    </section>

    <!-- Incluir el pie de página común a todas las páginas -->
    <?php include '../includes/pie_pagina.php'; ?>
</body>
</html>