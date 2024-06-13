<?php
// Iniciar una nueva sesión o reanudar la existente
session_start();
// Incluir configuración de la base de datos y funciones necesarias
require_once '../includes/config.php';
require_once '../includes/funciones.php';

// Verificar si la sesión está iniciada y si el usuario tiene el rol adecuado (archon)
verificarSesionIniciada(['archon']);

// Obtener la lista de todos los Archons de la base de datos
$sql_archons = "SELECT * FROM archons";
$stmt_archons = $conn->prepare($sql_archons);
$stmt_archons->execute();
$result_archons = $stmt_archons->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <!-- Establecer la configuración de la ventana gráfica para asegurar que la página se vea bien en dispositivos móviles -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Título de la página -->
    <title>Panel de Archons</title>
    <!-- Enlace al archivo de estilos CSS -->
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
    <!-- Incluir el encabezado común a todas las páginas -->
    <?php include '../includes/encabezado.php'; ?>

    <div class="panel">
        <h2>Gestión de Archons</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Apellido</th>
                        <th>Email</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Verificar si hay Archons registrados
                    if ($result_archons->num_rows > 0) {
                        // Iterar sobre cada Archon y mostrar sus datos en la tabla
                        while ($fila = $result_archons->fetch_assoc()) {
                            echo "<tr>
                                    <td>" . htmlspecialchars($fila['nombre']) . "</td>
                                    <td>" . htmlspecialchars($fila['apellido']) . "</td>
                                    <td>" . htmlspecialchars($fila['email']) . "</td>
                                    <td>";

                            $deshabilitar_eliminar = false;

                            // Verificar si el Archon actual es el mismo usuario logueado, deshabilitar eliminación si es el caso
                            if ($fila['id'] == $_SESSION['usuario_id']) {
                                $deshabilitar_eliminar = true;
                            }

                            // Enlace para editar el Archon
                            echo "<a href='editar_archon.php?id=" . htmlspecialchars($fila['id']) . "' class='btn'>Editar</a>";

                            if ($deshabilitar_eliminar) {
                                // Deshabilitar el botón de eliminar para el Archon actual
                                echo "<button disabled class='btn-disabled'>Eliminar</button>";
                            } else {
                                // Enlace para eliminar el Archon
                                echo "<a href='eliminar_archon.php?id=" . htmlspecialchars($fila['id']) . "' onclick='return confirmarEliminacion()' class='btn'>Eliminar</a>";
                            }

                            echo "</td>
                                  </tr>";
                        }
                    } else {
                        // Mostrar un mensaje si no hay Archons registrados
                        echo "<tr><td colspan='4'>No hay Archons registrados.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <!-- Enlace para crear un nuevo Archon -->
        <a href="crear_archon.php" class="btn-create">Crear Archon</a>
    </div>

    <!-- Incluir el pie de página común a todas las páginas -->
    <?php include '../includes/pie_pagina.php'; ?>
</body>
</html>

<script>
// Función para confirmar la eliminación de un Archon
function confirmarEliminacion() {
    return confirm('¿Estás seguro de que deseas eliminar este Archon?');
}
</script>