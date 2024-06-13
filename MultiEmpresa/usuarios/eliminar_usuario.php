<?php
// Iniciar una nueva sesión o reanudar la existente
session_start();
// Incluir configuración de la base de datos y funciones necesarias
require_once '../includes/funciones.php';
require_once '../notificaciones/funciones_notificaciones.php';

// Verificar si la sesión está iniciada y si el usuario tiene el rol adecuado
verificarSesionIniciada(['administrador', 'gerente', 'archon', 'administrador_base']);

include '../includes/config.php';

// Obtener el ID del usuario a eliminar desde la URL y escaparlo para evitar inyecciones SQL
$id_usuario = $conn->real_escape_string($_GET['id']);
$rol_usuario = $_SESSION['rol'];
$id_usuario_actual = $_SESSION['usuario_id'];
$id_empresa = isset($_SESSION['id_empresa']) ? $_SESSION['id_empresa'] : null;

// Obtener el rol y nombre del usuario que se va a eliminar
$sql_usuario = "SELECT rol, nombre FROM usuarios WHERE id = ?";
$stmt_usuario = $conn->prepare($sql_usuario);
$stmt_usuario->bind_param("i", $id_usuario);
$stmt_usuario->execute();
$result_usuario = $stmt_usuario->get_result();
$usuario_a_eliminar = $result_usuario->fetch_assoc();

if ($usuario_a_eliminar) {
    $rol_usuario_a_eliminar = $usuario_a_eliminar['rol'];
    $nombre_usuario_a_eliminar = $usuario_a_eliminar['nombre'];

    // Verificar que los gerentes no puedan eliminar administradores ni a sí mismos
    if ($rol_usuario === 'gerente' && ($rol_usuario_a_eliminar === 'administrador' || $id_usuario == $id_usuario_actual)) {
        $_SESSION['error'] = "No tienes permisos para eliminar este usuario.";
    } elseif ($rol_usuario === 'administrador' && ($rol_usuario_a_eliminar === 'archon' || $rol_usuario_a_eliminar === 'administrador_base')) {
        $_SESSION['error'] = "No tienes permisos para eliminar este usuario.";
    } elseif ($rol_usuario === 'administrador_base' && $rol_usuario_a_eliminar === 'archon') {
        $_SESSION['error'] = "No tienes permisos para eliminar este usuario.";
    } else {
        // Eliminar los links asociados a las tareas del usuario
        $sql_tareas_usuario = "SELECT id FROM tareas WHERE id_usuario = ?";
        $stmt_tareas_usuario = $conn->prepare($sql_tareas_usuario);
        $stmt_tareas_usuario->bind_param("i", $id_usuario);
        $stmt_tareas_usuario->execute();
        $result_tareas_usuario = $stmt_tareas_usuario->get_result();
        while ($tarea = $result_tareas_usuario->fetch_assoc()) {
            $id_tarea = $tarea['id'];
            $sql_eliminar_links_tareas = "DELETE FROM links_archivos WHERE id_tarea = ?";
            $stmt_eliminar_links_tareas = $conn->prepare($sql_eliminar_links_tareas);
            $stmt_eliminar_links_tareas->bind_param("i", $id_tarea);
            $stmt_eliminar_links_tareas->execute();
        }

        // Eliminar las notificaciones asociadas al usuario
        $sql_eliminar_notificaciones = "DELETE FROM notificaciones WHERE id_usuario = ?";
        $stmt_eliminar_notificaciones = $conn->prepare($sql_eliminar_notificaciones);
        $stmt_eliminar_notificaciones->bind_param("i", $id_usuario);
        $stmt_eliminar_notificaciones->execute();

        // Eliminar las entradas en Historial_Actividades
        $sql_eliminar_historial = "DELETE FROM historial_actividades WHERE usuario_modifico = ?";
        $stmt_eliminar_historial = $conn->prepare($sql_eliminar_historial);
        $stmt_eliminar_historial->bind_param("i", $id_usuario);
        $stmt_eliminar_historial->execute();

        // Eliminar las entradas en Links_Archivos
        $sql_eliminar_links = "DELETE FROM links_archivos WHERE id_usuario = ?";
        $stmt_eliminar_links = $conn->prepare($sql_eliminar_links);
        $stmt_eliminar_links->bind_param("i", $id_usuario);
        $stmt_eliminar_links->execute();

        // Eliminar las tareas asociadas al usuario
        $sql_eliminar_tareas = "DELETE FROM tareas WHERE id_usuario = ?";
        $stmt_eliminar_tareas = $conn->prepare($sql_eliminar_tareas);
        $stmt_eliminar_tareas->bind_param("i", $id_usuario);
        $stmt_eliminar_tareas->execute();

        // Eliminar el usuario de la base de datos
        $sql_eliminar_usuario = "DELETE FROM usuarios WHERE id = ?";
        $stmt_eliminar_usuario = $conn->prepare($sql_eliminar_usuario);
        $stmt_eliminar_usuario->bind_param("i", $id_usuario);

        if ($stmt_eliminar_usuario->execute()) {
            // Crear la notificación para informar sobre la eliminación del usuario
            $usuario_actual = $_SESSION['usuario_id'];
            $nombre_usuario_actual = obtenerNombreUsuario($conn, $usuario_actual);
            $accion = "{$nombre_usuario_actual} eliminó al {$rol_usuario_a_eliminar}: {$nombre_usuario_a_eliminar}";
            if ($id_empresa) {
                crearNotificacionAdminsYGerentes($conn, $id_empresa, $accion, $usuario_actual);
            } else {
                crearNotificacion($conn, $accion, $usuario_actual); // Notificar a Archons si no hay empresa
            }

            $_SESSION['mensaje'] = "Usuario eliminado exitosamente.";
        } else {
            $_SESSION['error'] = "Error al eliminar el usuario: " . $conn->error;
        }
    }
} else {
    $_SESSION['error'] = "Usuario no encontrado.";
}

// Redirigir de vuelta a la página de usuarios
header("Location: usuarios.php");
exit();