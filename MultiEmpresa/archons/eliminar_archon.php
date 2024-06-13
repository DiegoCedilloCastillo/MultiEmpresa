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

// Escapar el ID del Archon de la URL para evitar inyecciones SQL
$id_archon = $conn->real_escape_string($_GET['id']);

// Obtener el nombre del Archon que se va a eliminar
$sql_archon = "SELECT nombre FROM archons WHERE id = ?";
$stmt_archon = $conn->prepare($sql_archon);
$stmt_archon->bind_param("i", $id_archon);
$stmt_archon->execute();
$result_archon = $stmt_archon->get_result();
$archon_a_eliminar = $result_archon->fetch_assoc();

if ($archon_a_eliminar) {
    $nombre_archon_a_eliminar = $archon_a_eliminar['nombre'];

    // Eliminar el Archon de la base de datos
    $sql_eliminar_archon = "DELETE FROM archons WHERE id = ?";
    $stmt_eliminar_archon = $conn->prepare($sql_eliminar_archon);
    $stmt_eliminar_archon->bind_param("i", $id_archon);

    if ($stmt_eliminar_archon->execute()) {
        // Obtener el ID y nombre del usuario que realiza la acción
        $usuario_actual = $_SESSION['usuario_id'];
        $nombre_usuario_actual = obtenerNombreArchon($conn, $usuario_actual);
        // Crear el mensaje de notificación
        $accion = "{$nombre_usuario_actual} eliminó al Archon: {$nombre_archon_a_eliminar}";
        // Crear la notificación para todos los Archons
        crearNotificacionArchons($conn, $accion, $usuario_actual);

        // Establecer un mensaje de éxito en la sesión
        $_SESSION['mensaje'] = "Archon eliminado exitosamente.";
    } else {
        // Manejar error en la eliminación del Archon
        $_SESSION['error'] = "Error al eliminar el Archon: " . $conn->error;
    }
} else {
    // Manejar el caso en que el Archon no exista
    $_SESSION['error'] = "Archon no encontrado.";
}

// Redirigir a la página de Archons después de la operación
header("Location: archons.php");
exit();