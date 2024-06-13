<?php
// Iniciar una nueva sesión o reanudar la existente
session_start();

// Incluir los archivos necesarios
require_once '../includes/funciones.php';
require_once '../notificaciones/funciones_notificaciones.php';

// Verificar que la sesión esté iniciada y que el rol sea permitido
verificarSesionIniciada(['administrador', 'gerente', 'administrador_base']);

// Incluir configuración de base de datos
include '../includes/config.php';

// Obtener el ID de la tarea a eliminar desde la URL y sanitizarlo
$id_tarea = isset($_GET['id']) ? intval($_GET['id']) : null;

if ($id_tarea === null) {
    // Redirigir a la página principal si no se proporciona un ID de tarea
    header('Location: ../index.php');
    exit();
}

// Obtener datos de la tarea desde la base de datos
$sql_tarea = "SELECT * FROM Tareas WHERE id = ?";
$stmt_tarea = $conn->prepare($sql_tarea);
$stmt_tarea->bind_param("i", $id_tarea);
$stmt_tarea->execute();
$result_tarea = $stmt_tarea->get_result();
$tarea = $result_tarea->fetch_assoc();

if (!$tarea) {
    // Redirigir a la página de tareas si la tarea no existe
    header('Location: tareas.php');
    exit();
}

// Eliminar los links asociados a la tarea
$sql_eliminar_links = "DELETE FROM Links_Archivos WHERE id_tarea = ?";
$stmt_eliminar_links = $conn->prepare($sql_eliminar_links);
$stmt_eliminar_links->bind_param("i", $id_tarea);
$stmt_eliminar_links->execute();

// Eliminar la tarea de la base de datos
$sql_delete = "DELETE FROM Tareas WHERE id = ?";
$stmt_delete = $conn->prepare($sql_delete);
$stmt_delete->bind_param("i", $id_tarea);

if ($stmt_delete->execute()) {
    // Obtener información del usuario actual para crear la notificación
    $usuario_actual = $_SESSION['usuario_id'];
    $nombre_usuario_actual = $_SESSION['nombre'];
    $accion = "$nombre_usuario_actual ha eliminado tu tarea: " . htmlspecialchars($tarea['nombre']);
    
    // Crear notificación para el usuario asignado a la tarea
    crearNotificacionUsuario($conn, $tarea['id_usuario'], $accion);

    // Establecer mensaje de éxito y redirigir a la página de tareas
    $_SESSION['mensaje'] = "Tarea eliminada exitosamente.";
    header("Location: tareas.php");
    exit();
} else {
    // Establecer mensaje de error y redirigir a la página de tareas si falla la eliminación
    $_SESSION['error'] = "Error al eliminar la tarea: " . $stmt_delete->error;
    header("Location: tareas.php");
    exit();
}