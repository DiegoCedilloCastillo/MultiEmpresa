<?php
// Iniciar una nueva sesión o reanudar la existente
session_start();
require_once '../includes/funciones.php';
require_once '../notificaciones/funciones_notificaciones.php';
require_once '../includes/config.php';

// Verificar si la sesión está iniciada y si el usuario tiene el rol adecuado
verificarSesionIniciada(['administrador', 'archon', 'administrador_base']);

// Obtener la empresa del administrador o gerente desde la sesión
$id_empresa = $_SESSION['id_empresa'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener y escapar los datos del formulario
    $nombre_proyecto = $conn->real_escape_string($_POST['nombre_proyecto']);
    $descripcion = $conn->real_escape_string($_POST['descripcion']);
    $fecha_inicio = $conn->real_escape_string($_POST['fecha_inicio']);
    $fecha_fin = $conn->real_escape_string($_POST['fecha_fin']);
    $estado = 'pendiente'; // Estado inicial por defecto

    // Generar un ID general para el proyecto
    $id_general = generar_id($conn, 'proyecto');

    if ($id_general) {
        // Preparar la consulta SQL para insertar el nuevo proyecto
        $sql_proyecto = "INSERT INTO proyectos (id_general, nombre, descripcion, fecha_inicio, fecha_fin, estado, id_empresa) 
                         VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql_proyecto);
        $stmt->bind_param("isssssi", $id_general, $nombre_proyecto, $descripcion, $fecha_inicio, $fecha_fin, $estado, $id_empresa);

        if ($stmt->execute()) {
            $id_proyecto = $stmt->insert_id; // Obtener la ID específica generada automáticamente

            // Insertar la acción en el historial de actividades
            $sql_historial = "INSERT INTO historial_actividades (id_general, id_entidad, tipo_entidad, descripcion, estado, usuario_modifico, id_empresa)
                              VALUES (?, ?, 'proyecto', ?, ?, ?, ?)";
            $stmt_historial = $conn->prepare($sql_historial);
            $stmt_historial->bind_param("iissii", $id_general, $id_proyecto, $nombre_proyecto, $estado, $_SESSION['usuario_id'], $id_empresa);
            $stmt_historial->execute();

            // Crear la notificación para todos los usuarios relevantes
            $usuario_actual = $_SESSION['usuario_id'];
            $nombre_usuario_actual = obtenerNombreUsuario($conn, $usuario_actual);
            $accion = "$nombre_usuario_actual ha agregado un nuevo proyecto: $nombre_proyecto";
            crearNotificacionEmpresa($conn, $id_empresa, $accion, $usuario_actual);

            // Redirigir al usuario a la página de proyectos con un mensaje de éxito
            $_SESSION['mensaje'] = "Proyecto creado exitosamente.";
            header("Location: proyectos.php");
            exit();
        } else {
            // Manejo de errores en caso de fallo en la inserción del proyecto
            $error = "Error: " . $stmt->error;

            // Eliminar el ID general generado si la inserción del proyecto falla
            $conn->query("DELETE FROM ids WHERE id = $id_general");
        }
    } else {
        $error = "Error: no se pudo generar el ID general.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Proyecto</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
    <?php include '../includes/encabezado.php'; ?>

    <section class="form-container">
        <h2>Crear Proyecto</h2>
        <?php if (isset($error)) : ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form action="crear_proyecto.php" method="POST">
            <label for="nombre_proyecto">Nombre del Proyecto</label>
            <input type="text" id="nombre_proyecto" name="nombre_proyecto" required>
            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion" required></textarea>
            <label for="fecha_inicio">Fecha de Inicio</label>
            <input type="date" id="fecha_inicio" name="fecha_inicio" required>
            <label for="fecha_fin">Fecha de Fin</label>
            <input type="date" id="fecha_fin" name="fecha_fin" required>
            <button type="submit">Crear Proyecto</button>
        </form>
    </section>

    <?php include '../includes/pie_pagina.php'; ?>
</body>
</html>