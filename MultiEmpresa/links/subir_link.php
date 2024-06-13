<?php
// Iniciar una nueva sesión o reanudar la existente
session_start();
require_once '../includes/funciones.php';
require_once '../includes/config.php';
require_once '../notificaciones/funciones_notificaciones.php';

// Verificar si la sesión está iniciada y si el usuario tiene el rol adecuado
verificarSesionIniciada(['administrador', 'gerente', 'archon', 'administrador_base', 'usuario']);

// Obtener el ID de la tarea desde los parámetros de la URL
$id_tarea = isset($_GET['id_tarea']) ? intval($_GET['id_tarea']) : 0;

// Verificar que se haya proporcionado un ID de tarea válido
if ($id_tarea === 0) {
    echo "ID de tarea no especificado.";
    exit();
}

// Procesar el formulario de subida del enlace si se ha enviado una solicitud POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Escapar los datos del formulario para evitar inyecciones SQL
    $link = $conn->real_escape_string($_POST['link']);
    $descripcion = $conn->real_escape_string($_POST['descripcion']);
    $id_usuario = $_SESSION['usuario_id'];
    $nombre_usuario_actual = $_SESSION['nombre'];
    $rol_usuario_actual = $_SESSION['rol'];

    // Generar un ID general para el enlace
    $id_general = generar_id($conn, 'link');

    // Insertar el enlace en la base de datos
    $sql_insert_link = "INSERT INTO Links_Archivos (id_general, id_usuario, link, descripcion, id_tarea, id_proyecto) 
                        VALUES (?, ?, ?, ?, ?, (SELECT id_proyecto FROM Tareas WHERE id = ?))";
    $stmt_insert_link = $conn->prepare($sql_insert_link);
    $stmt_insert_link->bind_param("iissii", $id_general, $id_usuario, $link, $descripcion, $id_tarea, $id_tarea);

    // Verificar si la inserción del enlace fue exitosa
    if ($stmt_insert_link->execute()) {
        // Obtener los datos de la tarea para las notificaciones
        $sql_tarea = "SELECT nombre, id_usuario FROM Tareas WHERE id = ?";
        $stmt_tarea = $conn->prepare($sql_tarea);
        $stmt_tarea->bind_param("i", $id_tarea);
        $stmt_tarea->execute();
        $result_tarea = $stmt_tarea->get_result();
        $tarea = $result_tarea->fetch_assoc();
        $nombre_tarea = $tarea['nombre'];
        $id_usuario_asignado = $tarea['id_usuario'];

        // Crear el mensaje de notificación
        $mensaje = "$nombre_usuario_actual ha subido un enlace a la tarea '$nombre_tarea': $descripcion";

        // Gestionar las notificaciones según el rol del usuario y el usuario asignado a la tarea
        if ($rol_usuario_actual === 'usuario') {
            // Notificar a administradores y gerentes
            crearNotificacionAdminsYGerentes($conn, $_SESSION['id_empresa'], $mensaje, $id_usuario);
        } else {
            // Obtener el rol del usuario asignado a la tarea
            $sql_usuario_asignado = "SELECT rol FROM Usuarios WHERE id = ?";
            $stmt_usuario_asignado = $conn->prepare($sql_usuario_asignado);
            $stmt_usuario_asignado->bind_param("i", $id_usuario_asignado);
            $stmt_usuario_asignado->execute();
            $result_usuario_asignado = $stmt_usuario_asignado->get_result();
            $usuario_asignado = $result_usuario_asignado->fetch_assoc();
            $rol_usuario_asignado = $usuario_asignado['rol'];

            // Enviar notificaciones según el rol del usuario asignado
            if ($rol_usuario_asignado === 'usuario') {
                crearNotificacionAdminsYGerentes($conn, $_SESSION['id_empresa'], $mensaje, $id_usuario);
                crearNotificacionUsuario($conn, $id_usuario_asignado, $mensaje);
            } else {
                crearNotificacionAdminsYGerentes($conn, $_SESSION['id_empresa'], $mensaje, $id_usuario);
            }
        }

        // Establecer un mensaje de éxito y redirigir al listado de enlaces de la tarea
        $_SESSION['mensaje'] = "Link subido exitosamente.";
        header("Location: links_tareas.php?id_tarea=$id_tarea");
        exit();
    } else {
        // Mostrar un mensaje de error si la inserción falla
        echo "Error: " . $stmt_insert_link->error;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subir Link</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
    <!-- Incluir el encabezado común a todas las páginas -->
    <?php include '../includes/encabezado.php'; ?>

    <!-- Contenedor del formulario de subida de enlace -->
    <div class="form-container">
        <h2>Subir Link</h2>
        <form action="subir_link.php?id_tarea=<?= $id_tarea ?>" method="POST">
            <label for="link">Link:</label>
            <input type="url" id="link" name="link" required>
            <label for="descripcion">Descripción:</label>
            <textarea id="descripcion" name="descripcion" required></textarea>
            <button type="submit">Subir Link</button>
        </form>
    </div>

    <!-- Incluir el pie de página común a todas las páginas -->
    <?php include '../includes/pie_pagina.php'; ?>
</body>
</html>