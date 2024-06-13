<?php
// Iniciar sesión y verificar que el usuario esté autenticado
session_start();
require_once '../includes/funciones.php';
require_once '../includes/config.php';

// Verificar que la sesión esté iniciada y que el usuario tenga uno de los roles permitidos
verificarSesionIniciada(['administrador', 'gerente', 'usuario', 'archon', 'administrador_base']);

// Obtener el ID del proyecto desde la URL
$id_proyecto = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Si no se proporciona un ID de proyecto válido, redirigir a la lista de proyectos
if ($id_proyecto === 0) {
    header('Location: ../proyectos/proyectos.php');
    exit();
}

// Obtener datos del proyecto y de la empresa asociada
$sql_proyecto = "SELECT P.*, E.nombre AS empresa_nombre
                 FROM Proyectos P
                 LEFT JOIN Empresas E ON P.id_empresa = E.id
                 WHERE P.id = ?";
$stmt_proyecto = $conn->prepare($sql_proyecto);
$stmt_proyecto->bind_param("i", $id_proyecto);
$stmt_proyecto->execute();
$result_proyecto = $stmt_proyecto->get_result();
$proyecto = $result_proyecto->fetch_assoc();

// Si no se encuentra el proyecto, redirigir a la lista de proyectos
if (!$proyecto) {
    header('Location: ../proyectos/proyectos.php');
    exit();
}

// Obtener tareas asociadas al proyecto, incluyendo el nombre del usuario asignado
$sql_tareas = "SELECT T.*, U.nombre AS usuario_nombre
               FROM Tareas T
               LEFT JOIN Usuarios U ON T.id_usuario = U.id
               WHERE T.id_proyecto = ?";
$stmt_tareas = $conn->prepare($sql_tareas);
$stmt_tareas->bind_param("i", $id_proyecto);
$stmt_tareas->execute();
$result_tareas = $stmt_tareas->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Proyecto</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
    <!-- Incluir el encabezado común -->
    <?php include '../includes/encabezado.php'; ?>

    <!-- Contenedor principal para los detalles del proyecto -->
    <div class="panel">
        <h2>Detalles del Proyecto</h2>
        <p><strong>Nombre:</strong> <?= htmlspecialchars($proyecto['nombre']) ?></p>
        <p><strong>Descripción:</strong> <?= htmlspecialchars($proyecto['descripcion']) ?></p>
        <p><strong>Empresa:</strong> <?= htmlspecialchars($proyecto['empresa_nombre']) ?></p>
        <p><strong>Fecha de Inicio:</strong> <?= htmlspecialchars($proyecto['fecha_inicio']) ?></p>
        <p><strong>Fecha de Fin:</strong> <?= htmlspecialchars($proyecto['fecha_fin']) ?></p>
        <p><strong>Estado:</strong> <?= htmlspecialchars($proyecto['estado']) ?></p>

        <h3>Tareas Asociadas</h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Fecha de Inicio</th>
                        <th>Fecha de Fin</th>
                        <th>Estado</th>
                        <th>Asignado a</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Verificar si hay tareas asociadas al proyecto
                    if ($result_tareas->num_rows > 0) {
                        // Iterar sobre cada tarea y mostrar sus detalles
                        while ($tarea = $result_tareas->fetch_assoc()) {
                            echo "<tr>
                                    <td>" . htmlspecialchars($tarea['nombre']) . "</td>
                                    <td>" . htmlspecialchars($tarea['descripcion']) . "</td>
                                    <td>" . htmlspecialchars($tarea['fecha_inicio']) . "</td>
                                    <td>" . htmlspecialchars($tarea['fecha_fin']) . "</td>
                                    <td>" . htmlspecialchars($tarea['estado']) . "</td>
                                    <td>" . htmlspecialchars($tarea['usuario_nombre']) . "</td>
                                  </tr>";
                        }
                    } else {
                        // Mostrar un mensaje si no hay tareas asociadas
                        echo "<tr><td colspan='6'>No hay tareas asociadas a este proyecto.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Incluir el pie de página común -->
    <?php include '../includes/pie_pagina.php'; ?>
</body>
</html>