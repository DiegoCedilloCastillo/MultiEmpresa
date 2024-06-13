<?php
// Iniciar una nueva sesión o reanudar la existente
session_start();
require_once '../includes/funciones.php';
require_once '../notificaciones/funciones_notificaciones.php';
require_once '../includes/config.php';

// Verificar si la sesión está iniciada y si el usuario tiene el rol adecuado
verificarSesionIniciada(['administrador', 'gerente', 'archon', 'administrador_base']);

// Obtener el ID del link y el ID de la tarea desde los parámetros de la URL
$id_link = isset($_GET['id']) ? intval($_GET['id']) : 0;
$id_tarea = isset($_GET['id_tarea']) ? intval($_GET['id_tarea']) : 0;

// Verificar que se hayan proporcionado IDs válidos
if ($id_link === 0 || $id_tarea === 0) {
    header('Location: ../index.php');
    exit();
}

// Obtener los datos del link a editar
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

// Procesar el formulario de edición del link si se ha enviado una solicitud POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Escapar los datos del formulario para evitar inyecciones SQL
    $link_nuevo = $conn->real_escape_string($_POST['link']);
    $descripcion = $conn->real_escape_string($_POST['descripcion']);

    // Actualizar el link en la base de datos
    $sql_update = "UPDATE Links_Archivos SET link = ?, descripcion = ? WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("ssi", $link_nuevo, $descripcion, $id_link);

    // Verificar si la actualización del link fue exitosa
    if ($stmt_update->execute()) {
        // Obtener el ID y nombre del usuario que realiza la acción
        $id_usuario = $_SESSION['usuario_id'];
        $nombre_usuario = $_SESSION['nombre'];

        // Obtener el nombre de la tarea asociada al link
        $sql_tarea = "SELECT nombre FROM Tareas WHERE id = ?";
        $stmt_tarea = $conn->prepare($sql_tarea);
        $stmt_tarea->bind_param("i", $id_tarea);
        $stmt_tarea->execute();
        $result_tarea = $stmt_tarea->get_result();
        $tarea = $result_tarea->fetch_assoc();
        $nombre_tarea = $tarea['nombre'];

        // Crear la notificación sobre la actualización del link
        $accion = "$nombre_usuario ha actualizado un link en la tarea '$nombre_tarea'";
        crearNotificacionAdminsYGerentes($conn, $_SESSION['id_empresa'], $accion, $id_usuario);

        // Establecer un mensaje de éxito y redirigir al listado de enlaces de la tarea
        $_SESSION['mensaje'] = "Link actualizado exitosamente.";
        header("Location: links_tareas.php?id_tarea=$id_tarea");
        exit();
    } else {
        // Mostrar un mensaje de error si la actualización falla
        $error = "Error: " . $stmt_update->error;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Link</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
    <!-- Contenedor del formulario de edición del enlace -->
    <section class="form-container">
        <h2>Editar Link</h2>
        <!-- Mostrar mensajes de error, si los hay -->
        <?php if (isset($error)) : ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <!-- Formulario para editar el enlace -->
        <form action="editar_link.php?id=<?= $id_link ?>&id_tarea=<?= $id_tarea ?>" method="POST">
            <label for="link">Link</label>
            <input type="url" id="link" name="link" value="<?= htmlspecialchars($link['link']) ?>" required>
            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion" required><?= htmlspecialchars($link['descripcion']) ?></textarea>
            <button type="submit">Actualizar Link</button>
        </form>
    </section>
</body>
</html>