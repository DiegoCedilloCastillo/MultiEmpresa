<?php
// Iniciar una nueva sesión o reanudar la existente
session_start();
// Incluir configuración de la base de datos y funciones necesarias
require_once '../includes/config.php';
require_once '../includes/funciones.php';
require_once '../notificaciones/funciones_notificaciones.php';

// Verificar si la sesión está iniciada y si el usuario tiene el rol adecuado (archon)
verificarSesionIniciada(['archon']);

// Obtener el ID del Archon a editar desde la URL
$id_archon = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Obtener los datos actuales del Archon antes de actualizar
$sql_archon = "SELECT * FROM archons WHERE id = ?";
$stmt = $conn->prepare($sql_archon);
$stmt->bind_param("i", $id_archon);
$stmt->execute();
$result_archon = $stmt->get_result();
$archon = $result_archon->fetch_assoc();

if (!$archon) {
    // Manejar el caso en que el Archon no exista
    echo "<script>alert('Archon no encontrado.'); window.location.href = 'archons.php';</script>";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Escapar los datos del formulario para evitar inyecciones SQL
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $apellido = $conn->real_escape_string($_POST['apellido']);
    $email = $conn->real_escape_string($_POST['email']);

    // Actualizar los datos del Archon en la base de datos
    $sql_update = "UPDATE archons SET nombre = ?, apellido = ?, email = ? WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("sssi", $nombre, $apellido, $email, $id_archon);

    if ($stmt_update->execute()) {
        // Obtener el nombre del usuario que realiza la acción
        $usuario_actual_nombre = obtenerNombreArchon($conn, $_SESSION['usuario_id']);
        // Crear el mensaje de notificación
        $mensaje = "{$usuario_actual_nombre} actualizó los datos de {$archon['nombre']}";

        // Crear la notificación para todos los Archons, excluyendo el que realizó la acción
        crearNotificacionArchons($conn, $mensaje, $_SESSION['usuario_id']);

        // Redirigir después de la actualización exitosa
        header("Location: archons.php");
        exit();
    } else {
        // Manejar error en la actualización del Archon
        echo "<script>alert('Error al actualizar el Archon.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <!-- Establecer la configuración de la ventana gráfica para asegurar que la página se vea bien en dispositivos móviles -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Título de la página -->
    <title>Editar Archon</title>
    <!-- Enlace al archivo de estilos CSS -->
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
    <section class="form-container">
        <h2>Editar Archon</h2>
        <!-- Formulario para editar el Archon -->
        <form action="editar_archon.php?id=<?= htmlspecialchars($id_archon) ?>" method="POST">
            <label for="nombre">Nombre</label>
            <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($archon['nombre']) ?>" required>
            <label for="apellido">Apellido</label>
            <input type="text" id="apellido" name="apellido" value="<?= htmlspecialchars($archon['apellido']) ?>" required>
            <label for="email">Correo Electrónico</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($archon['email']) ?>" required>
            <button type="submit">Actualizar Archon</button>
        </form>
    </section>
</body>
</html>