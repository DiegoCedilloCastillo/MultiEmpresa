<?php
// Iniciar una nueva sesión o reanudar la existente
session_start();
require_once '../includes/funciones.php';
require_once '../includes/config.php';
require_once '../notificaciones/funciones_notificaciones.php';

// Verificar que el usuario esté autenticado
verificarAutenticacion();

// Verificar acceso de edición según el rol del usuario
verificarAccesoEdicionProyecto($_SESSION['rol']);

// Obtener la empresa del usuario desde la sesión
$id_empresa = $_SESSION['id_empresa'];

// Obtener el ID del proyecto desde la URL y escaparlo para prevenir inyecciones SQL
$id_proyecto = $conn->real_escape_string($_GET['id']);

// Obtener los datos del proyecto desde la base de datos
$sql_proyecto = "SELECT * FROM proyectos WHERE id = $id_proyecto";
$result_proyecto = $conn->query($sql_proyecto);

if ($result_proyecto->num_rows == 0) {
    // Si el proyecto no existe, mostrar un mensaje y terminar la ejecución
    echo "El proyecto no existe.";
    exit();
}

// Obtener los datos del proyecto en un arreglo asociativo
$proyecto = $result_proyecto->fetch_assoc();
$id_general = $proyecto['id_general']; // Obtener el id_general del proyecto

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener y escapar los datos del formulario
    $nombre_proyecto = $conn->real_escape_string($_POST['nombre_proyecto']);
    $descripcion = $conn->real_escape_string($_POST['descripcion']);
    $fecha_inicio = $conn->real_escape_string($_POST['fecha_inicio']);
    $fecha_fin = $conn->real_escape_string($_POST['fecha_fin']);
    $estado = $conn->real_escape_string($_POST['estado']);

    // Evitar cambiar el estado a pendiente si ya no está en pendiente
    if ($estado === 'pendiente' && $proyecto['estado'] !== 'pendiente') {
        $estado = $proyecto['estado'];
    }

    // Preparar la consulta SQL para actualizar los datos del proyecto
    $sql = "UPDATE proyectos SET nombre='$nombre_proyecto', descripcion='$descripcion', fecha_inicio='$fecha_inicio', fecha_fin='$fecha_fin', estado='$estado' WHERE id=$id_proyecto";
    
    if ($conn->query($sql) === TRUE) {
        // Insertar la acción en el historial de actividades
        $sql_historial = "INSERT INTO historial_actividades (id_general, id_entidad, tipo_entidad, descripcion, estado, usuario_modifico, id_empresa)
                          VALUES (?, ?, 'proyecto', ?, ?, ?, ?)";
        $stmt_historial = $conn->prepare($sql_historial);
        $stmt_historial->bind_param("iissii", $id_general, $id_proyecto, $nombre_proyecto, $estado, $_SESSION['usuario_id'], $id_empresa);
        $stmt_historial->execute();

        // Crear la notificación para la actualización del proyecto
        $nombre_usuario = $_SESSION['nombre'];
        $accion = "$nombre_usuario actualizó los detalles del proyecto: $nombre_proyecto";
        crearNotificacionActualizacion($conn, $accion, $_SESSION['usuario_id']);

        // Redirigir al usuario a la página de proyectos con un mensaje de éxito
        $_SESSION['mensaje'] = "Proyecto actualizado exitosamente.";
        header("Location: proyectos.php");
        exit();
    } else {
        // Manejo de errores en caso de fallo en la actualización del proyecto
        $error = "Error: " . $sql . "<br>" . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Proyecto</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
    <?php include '../includes/encabezado.php'; ?>

    <section class="form-container">
        <h2>Editar Proyecto</h2>
        <?php if (isset($error)) : ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form action="editar_proyecto.php?id=<?= $id_proyecto ?>" method="POST">
            <label for="nombre_proyecto">Nombre del Proyecto</label>
            <input type="text" id="nombre_proyecto" name="nombre_proyecto" value="<?= htmlspecialchars($proyecto['nombre']) ?>" required>
            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion" required><?= htmlspecialchars($proyecto['descripcion']) ?></textarea>
            <label for="fecha_inicio">Fecha de Inicio</label>
            <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?= $proyecto['fecha_inicio'] ?>" required>
            <label for="fecha_fin">Fecha de Fin</label>
            <input type="date" id="fecha_fin" name="fecha_fin" value="<?= $proyecto['fecha_fin'] ?>" required>
            <label for="estado">Estado</label>
            <select id="estado" name="estado">
                <option value="pendiente" <?= $proyecto['estado'] === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                <option value="en progreso" <?= $proyecto['estado'] === 'en progreso' ? 'selected' : '' ?>>En progreso</option>
                <option value="completado" <?= $proyecto['estado'] === 'completado' ? 'selected' : '' ?>>Completado</option>
            </select>
            <button type="submit">Actualizar Proyecto</button>
        </form>
    </section>

    <?php include '../includes/pie_pagina.php'; ?>
</body>
</html>