<?php
// Iniciar una nueva sesión o reanudar la existente
session_start();
// Incluir configuración de la base de datos y funciones necesarias
require_once '../includes/config.php';
require_once '../includes/funciones.php';
require_once '../notificaciones/funciones_notificaciones.php';

// Verificar si la sesión está iniciada y si el usuario tiene uno de los roles permitidos
verificarSesionIniciada(['administrador', 'gerente', 'archon', 'administrador_base']);

// Escapar el ID del contacto de la URL para evitar inyecciones SQL
$id_contacto = $conn->real_escape_string($_GET['id']);
$rol_usuario = $_SESSION['rol'];
$id_usuario_actual = $_SESSION['usuario_id'];
$id_empresa = isset($_SESSION['id_empresa']) ? $_SESSION['id_empresa'] : null;

// Obtener el nombre y tipo del contacto que se va a eliminar
$sql_contacto = "SELECT nombre, tipo, id_general FROM contactos WHERE id = ?";
$stmt_contacto = $conn->prepare($sql_contacto);
$stmt_contacto->bind_param("i", $id_contacto);
$stmt_contacto->execute();
$result_contacto = $stmt_contacto->get_result();
$contacto_a_eliminar = $result_contacto->fetch_assoc();

if ($contacto_a_eliminar) {
    $nombre_contacto = $contacto_a_eliminar['nombre'];
    $tipo_contacto = $contacto_a_eliminar['tipo'];
    $id_general = $contacto_a_eliminar['id_general'];

    // Verificar que los gerentes no puedan eliminar contactos de ciertas categorías
    if ($rol_usuario === 'gerente' && $tipo_contacto === 'administrador') {
        $_SESSION['error'] = "No tienes permisos para eliminar este contacto.";
    } else {
        // Eliminar el contacto de la tabla 'contactos'
        $sql_eliminar_contacto = "DELETE FROM contactos WHERE id = ?";
        $stmt_eliminar_contacto = $conn->prepare($sql_eliminar_contacto);
        $stmt_eliminar_contacto->bind_param("i", $id_contacto);

        if ($stmt_eliminar_contacto->execute()) {
            // Verificar si el ID está siendo referenciado por otras tablas antes de eliminar
            $sql_verificar_referencias = "SELECT COUNT(*) AS total FROM empresas WHERE id_general = ?";
            $stmt_verificar_referencias = $conn->prepare($sql_verificar_referencias);
            $stmt_verificar_referencias->bind_param("i", $id_general);
            $stmt_verificar_referencias->execute();
            $result_verificar_referencias = $stmt_verificar_referencias->get_result();
            $referencias = $result_verificar_referencias->fetch_assoc();

            if ($referencias['total'] == 0) {
                // No hay referencias, eliminar el ID
                $sql_eliminar_id = "DELETE FROM ids WHERE id = ?";
                $stmt_eliminar_id = $conn->prepare($sql_eliminar_id);
                $stmt_eliminar_id->bind_param("i", $id_general);
                $stmt_eliminar_id->execute();
            } else {
                $_SESSION['error'] = "No se pudo eliminar el ID asociado debido a restricciones de clave foránea.";
            }

            // Crear la notificación
            $nombre_usuario = obtenerNombreUsuario($conn, $_SESSION['usuario_id']);
            $accion = "$nombre_usuario ha eliminado el contacto: $nombre_contacto ($tipo_contacto)";
            crearNotificacionActualizacion($conn, $accion, $_SESSION['usuario_id']);

            // Establecer un mensaje de éxito en la sesión
            $_SESSION['mensaje'] = "Contacto eliminado exitosamente.";
        } else {
            // Manejar error en la eliminación del contacto
            $_SESSION['error'] = "Error al eliminar el contacto: " . $conn->error;
        }
    }
} else {
    // Manejar el caso en que el contacto no exista
    $_SESSION['error'] = "El contacto no existe.";
}

// Redirigir a la página de contactos después de la operación
header("Location: contactos.php");
exit();