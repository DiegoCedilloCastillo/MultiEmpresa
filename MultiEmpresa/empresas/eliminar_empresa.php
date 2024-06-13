<?php
// Iniciar una nueva sesión o reanudar la existente
session_start();
// Incluir funciones necesarias para verificar la sesión y el rol del usuario
require_once '../includes/funciones.php';
require_once '../includes/config.php';
require_once '../notificaciones/funciones_notificaciones.php';

// Verificar si el usuario está autenticado y tiene el rol adecuado (archon)
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'archon') {
    // Redirigir al formulario de inicio de sesión si no está autenticado o no tiene el rol adecuado
    header('Location: ../index.php');
    exit();
}

// Escapar el ID de la empresa de la URL para evitar inyecciones SQL
$id_empresa = $conn->real_escape_string($_GET['id']);

// Obtener el nombre de la empresa
$sql_empresa = "SELECT nombre FROM Empresas WHERE id = $id_empresa";
$result_empresa = $conn->query($sql_empresa);
$empresa = $result_empresa->fetch_assoc();

// Si la empresa existe, proceder con la eliminación
if ($empresa) {
    $nombre_empresa = $empresa['nombre'];

    // Contar el número de usuarios en la empresa
    $sql_contar_usuarios = "SELECT COUNT(*) as total FROM Usuarios WHERE id_empresa = $id_empresa";
    $result_contar_usuarios = $conn->query($sql_contar_usuarios);
    $conteo = $result_contar_usuarios->fetch_assoc();

    // Verificar que no haya más de un usuario asociado a la empresa
    if ($conteo['total'] > 1) {
        echo "<script>alert('No se puede eliminar la empresa porque aún hay más de un usuario asociado a ella.'); window.location.href = 'empresas.php';</script>";
        exit();
    }

    // Eliminar todas las notificaciones relacionadas con los usuarios de la empresa
    $conn->query("DELETE FROM Notificaciones WHERE id_usuario IN (SELECT id FROM Usuarios WHERE id_empresa = $id_empresa)");

    // Eliminar todos los registros relacionados con la empresa en otras tablas
    $conn->query("DELETE FROM Historial_Actividades WHERE id_empresa = $id_empresa");
    $conn->query("DELETE FROM Links_Archivos WHERE id_empresa = $id_empresa");
    $conn->query("DELETE FROM Tareas WHERE id_empresa = $id_empresa");
    $conn->query("DELETE FROM Proyectos WHERE id_empresa = $id_empresa");
    $conn->query("DELETE FROM Contactos WHERE id_empresa = $id_empresa");
    $conn->query("DELETE FROM Usuarios WHERE id_empresa = $id_empresa");

    // Eliminar la empresa
    $sql = "DELETE FROM Empresas WHERE id = $id_empresa";

    if ($conn->query($sql) === TRUE) {
        // Crear la notificación
        $nombre_usuario = $_SESSION['nombre'];
        $accion = "$nombre_usuario ha eliminado la empresa: $nombre_empresa";

        // Notificar solo a los Archons
        $sql_archons = "SELECT id FROM archons WHERE id != ?";
        $stmt_archons = $conn->prepare($sql_archons);
        $stmt_archons->bind_param("i", $_SESSION['usuario_id']);
        $stmt_archons->execute();
        $result_archons = $stmt_archons->get_result();

        while ($row = $result_archons->fetch_assoc()) {
            crearNotificacionUsuario($conn, $row['id'], $accion);
        }

        // Establecer un mensaje de éxito en la sesión y redirigir a la página de empresas
        $_SESSION['mensaje'] = "Empresa eliminada exitosamente.";
        header("Location: empresas.php");
        exit();
    } else {
        // Manejar error en la eliminación de la empresa
        $_SESSION['error'] = "Error al eliminar la empresa: " . $conn->error;
        header("Location: empresas.php");
        exit();
    }
} else {
    // Manejar el caso en que la empresa no exista
    $_SESSION['error'] = "La empresa no existe.";
    header("Location: empresas.php");
    exit();
}