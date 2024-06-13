<?php
// Iniciar una nueva sesión o reanudar la existente
session_start();
// Incluir funciones necesarias para verificar la sesión y el rol del usuario
require_once '../includes/funciones.php';
require_once '../notificaciones/funciones_notificaciones.php';

// Verificar si la sesión está iniciada y si el usuario tiene el rol adecuado (archon o administrador)
verificarSesionIniciada(['archon', 'administrador']);

// Incluir configuración de la base de datos
include '../includes/config.php';

// Escapar el ID de la empresa de la URL para evitar inyecciones SQL
$id_empresa = $conn->real_escape_string($_GET['id']);

// Obtener los datos actuales de la empresa
$sql_empresa = "SELECT * FROM empresas WHERE id = ?";
$stmt_empresa = $conn->prepare($sql_empresa);
$stmt_empresa->bind_param("i", $id_empresa);
$stmt_empresa->execute();
$result_empresa = $stmt_empresa->get_result();
$empresa = $result_empresa->fetch_assoc();
$nombre_empresa_anterior = $empresa['nombre'];

// Verificar si la solicitud es POST, lo que indica que el formulario ha sido enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Escapar los datos del formulario para evitar inyecciones SQL
    $nombre_empresa = $conn->real_escape_string($_POST['nombre_empresa']);
    $direccion = $conn->real_escape_string($_POST['direccion']);
    $telefono = $conn->real_escape_string($_POST['telefono']);
    $email = $conn->real_escape_string($_POST['email']);

    // Actualizar los datos de la empresa en la base de datos
    $sql = "UPDATE empresas SET nombre=?, direccion=?, telefono=?, email=? WHERE id=?";
    $stmt_update = $conn->prepare($sql);
    $stmt_update->bind_param("ssssi", $nombre_empresa, $direccion, $telefono, $email, $id_empresa);

    if ($stmt_update->execute()) {
        // Obtener el nombre del usuario actual para la notificación
        $nombre_usuario_actual = obtenerNombreArchon($conn, $_SESSION['usuario_id']);
        $accion = "$nombre_usuario_actual ha actualizado los datos de la empresa: $nombre_empresa_anterior";

        // Notificar a todos los Archons y al administrador base
        crearNotificacionArchons($conn, $accion, $_SESSION['usuario_id'], $id_empresa);

        // Establecer un mensaje de éxito en la sesión y redirigir a la página de empresas
        $_SESSION['mensaje'] = "Empresa actualizada exitosamente.";
        header("Location: empresas.php");
        exit();
    } else {
        // Manejar error en la actualización de la empresa
        $error = "Error: " . $stmt_update->error;
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
    <title>Editar Empresa</title>
    <!-- Enlace al archivo de estilos CSS -->
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
    <section class="form-container">
        <h2>Editar Empresa</h2>
        <!-- Mostrar el mensaje de error si existe -->
        <?php if (isset($error)) : ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <!-- Formulario para editar la empresa -->
        <form action="editar_empresa.php?id=<?= htmlspecialchars($id_empresa) ?>" method="POST">
            <label for="nombre_empresa">Nombre de la Empresa</label>
            <input type="text" id="nombre_empresa" name="nombre_empresa" value="<?= htmlspecialchars($empresa['nombre']) ?>" required>
            <label for="direccion">Dirección</label>
            <input type="text" id="direccion" name="direccion" value="<?= htmlspecialchars($empresa['direccion']) ?>" required>
            <label for="telefono">Teléfono</label>
            <input type="text" id="telefono" name="telefono" value="<?= htmlspecialchars($empresa['telefono']) ?>" required>
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($empresa['email']) ?>" required>
            <button type="submit">Actualizar Empresa</button>
        </form>
    </section>
</body>
</html>