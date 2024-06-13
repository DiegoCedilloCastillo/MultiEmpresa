<?php
// Iniciar una nueva sesión o reanudar la existente
session_start();
// Incluir funciones necesarias para verificar la sesión y el rol del usuario
require_once '../includes/funciones.php';

// Verificar si el usuario está autenticado y tiene el rol adecuado (archon)
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'archon') {
    // Redirigir al formulario de inicio de sesión si no está autenticado o no tiene el rol adecuado
    header('Location: ../index.php');
    exit();
}

// Incluir configuración de la base de datos
include '../includes/config.php';

// Obtener la lista de empresas de la base de datos
$sql_empresas = "SELECT id, nombre, direccion, telefono, email FROM Empresas";
$result_empresas = $conn->query($sql_empresas);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <!-- Establecer la configuración de la ventana gráfica para asegurar que la página se vea bien en dispositivos móviles -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Título de la página -->
    <title>Ver Empresas</title>
    <!-- Enlace al archivo de estilos CSS -->
    <link rel="stylesheet" href="../css/estilos.css">
    <!-- Script para confirmar la eliminación de una empresa -->
    <script>
        function confirmarEliminacion() {
            return confirm("¿Estás seguro de que deseas eliminar esta empresa?");
        }
    </script>
</head>
<body>
    <!-- Incluir el encabezado común a todas las páginas -->
    <?php include '../includes/encabezado.php'; ?>

    <div class="panel">
        <h2>Ver Empresas</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Dirección</th>
                        <th>Teléfono</th>
                        <th>Email</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Verificar si hay empresas registradas
                    if ($result_empresas->num_rows > 0) {
                        // Iterar sobre cada empresa y mostrar sus datos en la tabla
                        while ($fila = $result_empresas->fetch_assoc()) {
                            echo "<tr>
                                    <td>" . htmlspecialchars($fila['id']) . "</td>
                                    <td>" . htmlspecialchars($fila['nombre']) . "</td>
                                    <td>" . htmlspecialchars($fila['direccion']) . "</td>
                                    <td>" . htmlspecialchars($fila['telefono']) . "</td>
                                    <td>" . htmlspecialchars($fila['email']) . "</td>
                                    <td>
                                        <a href='editar_empresa.php?id=" . htmlspecialchars($fila['id']) . "'>Editar</a>
                                        <a href='eliminar_empresa.php?id=" . htmlspecialchars($fila['id']) . "' onclick='return confirmarEliminacion()'>Eliminar</a>
                                    </td>
                                  </tr>";
                        }
                    } else {
                        // Mostrar un mensaje si no hay empresas registradas
                        echo "<tr><td colspan='6'>No hay empresas registradas.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Incluir el pie de página común a todas las páginas -->
    <?php include '../includes/pie_pagina.php'; ?>
</body>
</html>