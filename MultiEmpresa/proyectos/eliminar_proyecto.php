<?php
// Iniciar una nueva sesión o reanudar la existente
session_start();
require_once '../includes/funciones.php';
require_once '../notificaciones/funciones_notificaciones.php';

// Verificar que el usuario esté autenticado
verificarAutenticacion();

// Verificar acceso de edición según el rol del usuario
verificarAccesoEdicionProyecto($_SESSION['rol']);

// Incluir la configuración de la base de datos
include '../includes/config.php';

// Obtener el ID del proyecto desde la URL y escaparlo para prevenir inyecciones SQL
$id_proyecto = isset($_GET['id']) ? $conn->real_escape_string($_GET['id']) : null;

if ($id_proyecto === null) {
    // Si no se especifica el ID del proyecto, redirigir a la página principal
    header('Location: ../index.php');
    exit();
}

// Obtener datos del proyecto desde la base de datos
$sql_proyecto = "SELECT * FROM Proyectos WHERE id = $id_proyecto";
$result_proyecto = $conn->query($sql_proyecto);
$proyecto = $result_proyecto->fetch_assoc();

if (!$proyecto) {
    // Si el proyecto no existe, redirigir a la página de proyectos
    header('Location: proyectos.php');
    exit();
}

// Eliminar los links asociados a las tareas del proyecto
$sql_obtener_tareas = "SELECT id FROM Tareas WHERE id_proyecto = $id_proyecto";
$result_tareas = $conn->query($sql_obtener_tareas);
while ($tarea = $result_tareas->fetch_assoc()) {
    $id_tarea = $tarea['id'];
    $sql_eliminar_links = "DELETE FROM Links_Archivos WHERE id_tarea = $id_tarea";
    $conn->query($sql_eliminar_links);
}

// Eliminar las tareas asociadas al proyecto
$sql_eliminar_tareas = "DELETE FROM Tareas WHERE id_proyecto = $id_proyecto";
$conn->query($sql_eliminar_tareas);

// Eliminar el proyecto
$sql_delete = "DELETE FROM Proyectos WHERE id = $id_proyecto";
if ($conn->query($sql_delete) === TRUE) {
    // Crear la notificación
    $usuario_actual = $_SESSION['usuario_id'];
    $nombre_usuario_actual = $_SESSION['nombre'];
    $accion = "$nombre_usuario_actual eliminó el proyecto: " . $proyecto['nombre'];
    crearNotificacionEmpresa($conn, $_SESSION['id_empresa'], $accion, $usuario_actual);

    // Redirigir con un mensaje de éxito
    $_SESSION['mensaje'] = "Proyecto eliminado exitosamente.";
    header("Location: proyectos.php");
    exit();
} else {
    // Redirigir con un mensaje de error en caso de fallo en la eliminación
    $_SESSION['error'] = "Error al eliminar el proyecto: " . $conn->error;
    header("Location: proyectos.php");
    exit();
}