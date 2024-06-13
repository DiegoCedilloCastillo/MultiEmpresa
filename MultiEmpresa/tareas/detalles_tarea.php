<?php
// Iniciar sesión y verificar que el usuario esté autenticado
session_start();
require_once '../includes/funciones.php';

// Verificar que la sesión esté iniciada
if (!isset($_SESSION['usuario_id'])) {
    // Redirigir al índice si no hay una sesión iniciada
    header('Location: ../index.php');
    exit();
}

// Incluir configuración de la base de datos
include '../includes/config.php';

// Obtener el ID de la tarea desde la URL
$id_tarea = $conn->real_escape_string($_GET['id']);

// Consultar los datos de la tarea, el proyecto y el usuario asignado
$sql_tarea = "SELECT T.*, P.nombre AS nombre_proyecto, U.nombre AS nombre_usuario
              FROM Tareas T
              JOIN Proyectos P ON T.id_proyecto = P.id
              JOIN Usuarios U ON T.id_usuario = U.id
              WHERE T.id = $id_tarea";
$result_tarea = $conn->query($sql_tarea);

// Obtener el resultado de la consulta como un array asociativo
$tarea = $result_tarea->fetch_assoc();

// Verificar que la tarea existe
if (!$tarea) {
    // Redirigir a la lista de tareas si la tarea no existe
    header('Location: ../tareas/tareas.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles de la Tarea</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
    <!-- Incluir el encabezado común -->
    <?php include '../includes/encabezado.php'; ?>

    <!-- Contenedor principal para los detalles de la tarea -->
    <div class="panel">
        <h2>Detalles de la Tarea</h2>
        <!-- Mostrar detalles específicos de la tarea -->
        <p><strong>Nombre:</strong> <?= htmlspecialchars($tarea['nombre']) ?></p>
        <p><strong>Descripción:</strong> <?= htmlspecialchars($tarea['descripcion']) ?></p>
        <p><strong>Proyecto:</strong> <?= htmlspecialchars($tarea['nombre_proyecto']) ?></p>
        <p><strong>Asignado a:</strong> <?= htmlspecialchars($tarea['nombre_usuario']) ?></p>
        <p><strong>Fecha de Inicio:</strong> <?= htmlspecialchars($tarea['fecha_inicio']) ?></p>
        <p><strong>Fecha de Fin:</strong> <?= htmlspecialchars($tarea['fecha_fin']) ?></p>
        <p><strong>Estado:</strong> <?= htmlspecialchars($tarea['estado']) ?></p>
    </div>

    <!-- Incluir el pie de página común -->
    <?php include '../includes/pie_pagina.php'; ?>
</body>
</html>