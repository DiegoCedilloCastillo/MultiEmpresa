<?php
// Iniciar una nueva sesión o reanudar la existente
session_start();
require_once '../includes/funciones.php';
require_once '../includes/config.php';

// Verificar si la sesión está iniciada y si el usuario tiene el rol adecuado
verificarSesionIniciada(['administrador', 'gerente', 'usuario', 'archon', 'administrador_base']);

// Obtener los datos del usuario desde la sesión
$id_usuario = $_SESSION['usuario_id'];
$id_empresa = $_SESSION['id_empresa'];
$rol_usuario = $_SESSION['rol'];

// Obtener el ID de la tarea desde los parámetros de la URL
$id_tarea = isset($_GET['id_tarea']) ? intval($_GET['id_tarea']) : 0;

// Verificar que se haya proporcionado un ID de tarea válido
if ($id_tarea === 0) {
    header('Location: ../index.php');
    exit();
}

// Verificar si la tarea existe en la base de datos
$sql_verificar_tarea = "SELECT * FROM Tareas WHERE id = ?";
$stmt_verificar_tarea = $conn->prepare($sql_verificar_tarea);
$stmt_verificar_tarea->bind_param("i", $id_tarea);
$stmt_verificar_tarea->execute();
$result_verificar_tarea = $stmt_verificar_tarea->get_result();
if ($result_verificar_tarea->num_rows == 0) {
    echo "La tarea no existe.";
    exit();
}

// Obtener la lista de links asociados a la tarea desde la base de datos
$sql_links = "SELECT * FROM Links_Archivos WHERE id_tarea = ?";
$stmt_links = $conn->prepare($sql_links);
$stmt_links->bind_param("i", $id_tarea);
$stmt_links->execute();
$result_links = $stmt_links->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Links de Archivos de la Tarea</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
    <?php include '../includes/encabezado.php'; ?>

    <div class="panel">
        <h2>Links de Archivos de la Tarea</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Link</th>
                        <th>Descripción</th>
                        <?php if ($_SESSION['rol'] !== 'usuario'): ?>
                        <th>Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Verificar si hay links registrados para la tarea
                    if ($result_links->num_rows > 0) {
                        // Recorrer cada link y mostrarlo en la tabla
                        while ($fila = $result_links->fetch_assoc()) {
                            echo "<tr>
                                    <td>{$fila['id']}</td>
                                    <td><a href='{$fila['link']}' target='_blank'>{$fila['link']}</a></td>
                                    <td>{$fila['descripcion']}</td>";
                            // Mostrar las acciones solo si el rol del usuario no es 'usuario'
                            if ($_SESSION['rol'] !== 'usuario') {
                                echo "<td>";
                                echo "<a href='editar_link.php?id={$fila['id']}&id_tarea={$id_tarea}' class='btn'>Editar</a>";
                                echo "<a href='eliminar_link.php?id={$fila['id']}&id_tarea={$id_tarea}' onclick='return confirmarEliminacion()' class='btn'>Eliminar</a>";
                                echo "</td>";
                            }
                            echo "</tr>";
                        }
                    } else {
                        // Mostrar un mensaje si no hay links registrados
                        echo "<tr><td colspan='";
                        echo $_SESSION['rol'] !== 'usuario' ? "4" : "3";
                        echo "'>No hay links registrados.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <!-- Botón para subir un nuevo link -->
        <a href="subir_link.php?id_tarea=<?php echo $id_tarea; ?>" class="btn-create">Subir Link</a>
    </div>

    <?php include '../includes/pie_pagina.php'; ?>
</body>
</html>

<script>
// Función para confirmar la eliminación de un link
function confirmarEliminacion() {
    return confirm('¿Estás seguro de que deseas eliminar este link?');
}
</script>