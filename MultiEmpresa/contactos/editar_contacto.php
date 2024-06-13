<?php
// Iniciar una nueva sesión o reanudar la existente
session_start();
// Incluir configuración de la base de datos y funciones necesarias
require_once '../includes/config.php';
require_once '../includes/funciones.php';
require_once '../notificaciones/funciones_notificaciones.php';

// Verificar si la sesión está iniciada y si el usuario tiene uno de los roles permitidos
verificarSesionIniciada(['administrador', 'gerente', 'archon', 'administrador_base']);

// Obtener el ID del contacto a editar desde la URL
$id_contacto = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener los datos del formulario
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $tipo = $conn->real_escape_string($_POST['tipo']);
    $empresa = $conn->real_escape_string($_POST['empresa']);
    $telefono = $conn->real_escape_string($_POST['telefono']);
    $email = $conn->real_escape_string($_POST['email']);

    // Obtener los datos actuales del contacto antes de actualizar
    $sql_contacto_actual = "SELECT nombre, tipo FROM contactos WHERE id = ?";
    $stmt_contacto_actual = $conn->prepare($sql_contacto_actual);
    $stmt_contacto_actual->bind_param("i", $id_contacto);
    $stmt_contacto_actual->execute();
    $result_contacto_actual = $stmt_contacto_actual->get_result();
    $contacto_actual = $result_contacto_actual->fetch_assoc();

    $nombre_anterior = $contacto_actual['nombre'];
    $tipo_anterior = $contacto_actual['tipo'];

    // Actualizar los datos del contacto en la base de datos
    $sql_update = "UPDATE contactos SET nombre = ?, tipo = ?, empresa = ?, telefono = ?, email = ? WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("sssssi", $nombre, $tipo, $empresa, $telefono, $email, $id_contacto);

    if ($stmt_update->execute()) {
        // Obtener el nombre del usuario que realiza la acción
        $usuario_actual_nombre = obtenerNombreUsuario($conn, $_SESSION['usuario_id']);
        
        // Crear el mensaje de notificación
        $mensaje = "{$usuario_actual_nombre} actualizó los datos del contacto: {$nombre_anterior} ({$tipo_anterior})";
        
        // Crear notificación para admins y gerentes
        crearNotificacionAdminsYGerentes($conn, $_SESSION['id_empresa'], $mensaje, $_SESSION['usuario_id']);

        // Redirigir después de la actualización exitosa
        $_SESSION['mensaje'] = "Contacto actualizado exitosamente.";
        header("Location: contactos.php");
        exit();
    } else {
        // Manejar error en la actualización del contacto
        $error = "Error: " . $stmt_update->error;
    }
}

// Obtener datos del contacto desde la base de datos
$sql_contacto = "SELECT * FROM contactos WHERE id = ?";
$stmt = $conn->prepare($sql_contacto);
$stmt->bind_param("i", $id_contacto);
$stmt->execute();
$result_contacto = $stmt->get_result();
$contacto = $result_contacto->fetch_assoc();

if (!$contacto) {
    // Manejar el caso en que el contacto no exista
    echo "<script>alert('Contacto no encontrado.'); window.location.href = 'contactos.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <!-- Establecer la configuración de la ventana gráfica para asegurar que la página se vea bien en dispositivos móviles -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Título de la página -->
    <title>Editar Contacto</title>
    <!-- Enlace al archivo de estilos CSS -->
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
    <section class="form-container">
        <h2>Editar Contacto</h2>
        <!-- Mostrar el mensaje de error si existe -->
        <?php if (isset($error)) : ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <!-- Formulario para editar el contacto -->
        <form action="editar_contacto.php?id=<?= htmlspecialchars($id_contacto) ?>" method="POST">
            <label for="nombre">Nombre</label>
            <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($contacto['nombre']) ?>" required>
            <label for="tipo">Tipo</label>
            <select id="tipo" name="tipo" required>
                <option value="proveedor" <?= $contacto['tipo'] === 'proveedor' ? 'selected' : '' ?>>Proveedor</option>
                <option value="cliente" <?= $contacto['tipo'] === 'cliente' ? 'selected' : '' ?>>Cliente</option>
                <option value="socio" <?= $contacto['tipo'] === 'socio' ? 'selected' : '' ?>>Socio</option>
            </select>
            <label for="empresa">Empresa</label>
            <input type="text" id="empresa" name="empresa" value="<?= htmlspecialchars($contacto['empresa']) ?>" required>
            <label for="telefono">Teléfono</label>
            <input type="text" id="telefono" name="telefono" value="<?= htmlspecialchars($contacto['telefono']) ?>" required>
            <label for="email">Correo Electrónico</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($contacto['email']) ?>" required>
            <button type="submit">Guardar Cambios</button>
        </form>
    </section>
</body>
</html>