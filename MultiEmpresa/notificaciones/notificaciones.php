<?php
// Inicia una nueva sesión o reanuda la existente
session_start();

// Incluye las funciones y configuraciones necesarias
require_once '../includes/funciones.php';
require_once '../includes/config.php';

// Verifica si el usuario está autenticado
if (!isset($_SESSION['usuario_id'])) {
    // Redirige al formulario de inicio de sesión si no está autenticado
    header('Location: ../index.php');
    exit();
}

// Obtiene el ID del usuario de la sesión
$id_usuario = $_SESSION['usuario_id'];

// Maneja la solicitud POST para vaciar todas las notificaciones
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['vaciar_todas'])) {
    // Prepara y ejecuta la consulta para eliminar todas las notificaciones del usuario
    $sql_vaciar_todas = "DELETE FROM notificaciones WHERE id_usuario = ?";
    $stmt_vaciar_todas = $conn->prepare($sql_vaciar_todas);
    $stmt_vaciar_todas->bind_param("i", $id_usuario);
    if (!$stmt_vaciar_todas->execute()) {
        die('Error al vaciar todas las notificaciones: ' . $stmt_vaciar_todas->error);
    }
    $stmt_vaciar_todas->close();
}
// Maneja la solicitud POST para marcar una notificación como leída
elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['marcar_leida'])) {
    // Escapa el ID de la notificación para evitar inyecciones SQL
    $id_notificacion = $conn->real_escape_string($_POST['id_notificacion']);
    // Prepara y ejecuta la consulta para actualizar el estado de la notificación a leída
    $sql_marcar_leida = "UPDATE notificaciones SET leida = TRUE WHERE id = ? AND id_usuario = ?";
    $stmt_marcar_leida = $conn->prepare($sql_marcar_leida);
    $stmt_marcar_leida->bind_param("ii", $id_notificacion, $id_usuario);
    if (!$stmt_marcar_leida->execute()) {
        die('Error al marcar la notificación como leída: ' . $stmt_marcar_leida->error);
    }
    $stmt_marcar_leida->close();
}

// Prepara y ejecuta la consulta para obtener las notificaciones del usuario
$sql_notificaciones = "SELECT * FROM notificaciones WHERE id_usuario = ? ORDER BY fecha DESC";
$stmt_notificaciones = $conn->prepare($sql_notificaciones);
$stmt_notificaciones->bind_param("i", $id_usuario);
$stmt_notificaciones->execute();
$result_notificaciones = $stmt_notificaciones->get_result();

if (!$result_notificaciones) {
    die('Error en la consulta: ' . $stmt_notificaciones->error);
}

// Almacena las notificaciones en un arreglo
$data = [];
while ($row = $result_notificaciones->fetch_assoc()) {
    $data[] = $row;
}
$stmt_notificaciones->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificaciones</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
    <!-- Incluir el encabezado común a todas las páginas -->
    <?php include '../includes/encabezado.php'; ?>

    <section class="form-container">
        <h2>Notificaciones</h2>
        <div class="notificaciones">
            <!-- Mostrar el número de notificaciones obtenidas -->
            <?php
            echo "<p>Número de notificaciones: " . count($data) . "</p>";
            if (count($data) > 0) {
                // Mostrar cada notificación en un div separado
                foreach ($data as $row) {
                    echo "<div class='notificacion'>";
                    echo "<h4>Notificación</h4>";
                    if (isset($row['mensaje']) && isset($row['fecha'])) {
                        echo "<p>" . htmlspecialchars($row['mensaje']) . "</p>";
                        echo "<p class='fecha'>" . date('d/m/Y H:i:s', strtotime($row['fecha'])) . "</p>";
                        if (!$row['leida']) {
                            // Formulario para marcar la notificación como leída
                            echo "<form method='POST' action='notificaciones.php'>";
                            echo "<input type='hidden' name='id_notificacion' value='" . $row['id'] . "'>";
                            echo "<button type='submit' name='marcar_leida'>Marcar como leída</button>";
                            echo "</form>";
                        }
                    } else {
                        echo "<p>Error: mensaje o fecha no definidos.</p>";
                    }
                    echo "</div>";
                }
            } else {
                echo "<p>No tienes notificaciones.</p>";
            }
            ?>
        </div>
        <!-- Formulario para vaciar todas las notificaciones -->
        <form method="POST" action="notificaciones.php">
            <button type="submit" name="vaciar_todas">Vaciar todas las notificaciones</button>
        </form>
    </section>

    <!-- Incluir el pie de página común a todas las páginas -->
    <?php include '../includes/pie_pagina.php'; ?>
</body>
</html>