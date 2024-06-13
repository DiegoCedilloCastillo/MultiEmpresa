<?php
// Iniciar una nueva sesión o reanudar la existente
session_start();
require_once '../includes/funciones.php';
require_once '../notificaciones/funciones_notificaciones.php';
require_once '../includes/config.php';

// Verificar si la sesión está iniciada y si el usuario tiene el rol adecuado
verificarSesionIniciada(['administrador', 'gerente', 'archon', 'administrador_base']);

// Obtener el ID del link desde los parámetros de la URL
$id_link = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Verificar que se haya proporcionado un ID válido
if ($id_link === 0) {
    header('Location: ../index.php');
    exit();
}

// Obtener los datos del link a eliminar
$sql_link = "SELECT * FROM Links_Archivos WHERE id = ?";
$stmt_link = $conn->prepare($sql_link);
$stmt_link->bind_param("i", $id_link);
$stmt_link->execute();
$result_link = $stmt_link->get_result();
$link = $result_link->fetch_assoc();

// Verificar que el link exista
if (!$link) {
    header('Location: ../index.php');
    exit();
}

// Obtener el ID y nombre del usuario que realiza la acción
$id_usuario = $_SESSION['usuario_id'];
$nombre_usuario = $_SESSION['nombre'];

// Obtener el nombre de la tarea asociada al link
if ($link['id_tarea']) {
    $sql_tarea = "SELECT nombre FROM Tareas WHERE id = ?";
    $stmt_tarea = $conn->prepare($sql_tarea);
    $stmt_tarea->bind_param("i", $link['id_tarea']);
    $stmt_tarea->execute();
    $result_tarea = $stmt_tarea->get_result();
    $entidad = $result_tarea->fetch_assoc();
    $nombre_entidad = "tarea: " . $entidad['nombre'];
}

// Eliminar el link de la base de datos
$sql_delete = "DELETE FROM Links_Archivos WHERE id = ?";
$stmt_delete = $conn->prepare($sql_delete);
$stmt_delete->bind_param("i", $id_link);

// Verificar si la eliminación del link fue exitosa
if ($stmt_delete->execute()) {
    // Crear la notificación sobre la eliminación del link
    $accion = "$nombre_usuario ha eliminado un link en la $nombre_entidad";
    $sql_usuarios = "SELECT id FROM Usuarios WHERE (rol IN ('administrador', 'gerente') OR id = ?) AND id != ?";
    $stmt_usuarios = $conn->prepare($sql_usuarios);
    $stmt_usuarios->bind_param("ii", $link['id_usuario'], $id_usuario);
    $stmt_usuarios->execute();
    $result_usuarios = $stmt_usuarios->get_result();
    
    // Notificar a los usuarios correspondientes
    while ($row = $result_usuarios->fetch_assoc()) {
        crearNotificacionUsuario($conn, $row['id'], $accion);
    }

    // Establecer un mensaje de éxito y redirigir al listado de enlaces de la tarea
    $_SESSION['mensaje'] = "Link eliminado exitosamente.";
    header("Location: links_tareas.php?id_tarea=" . $link['id_tarea']);
    exit();
} else {
    // Mostrar un mensaje de error si la eliminación falla
    $error = "Error: " . $stmt_delete->error;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminar Link</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
    <?php include '../includes/encabezado.php'; ?>

    <!-- Contenedor del mensaje de eliminación del enlace -->
    <section class="form-container">
        <h2>Eliminar Link</h2>
        <!-- Mostrar mensajes de error, si los hay -->
        <?php if (isset($error)) : ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
    </section>

    <?php include '../includes/pie_pagina.php'; ?>
</body>
</html>