<?php
// Iniciar una nueva sesión o reanudar la sesión existente
session_start();

// Incluir archivos necesarios para funciones comunes y configuración de base de datos
require_once '../includes/funciones.php';
require_once '../includes/config.php';

// Verificar que la sesión esté iniciada y que el rol del usuario sea permitido
verificarSesionIniciada(['administrador', 'gerente', 'usuario', 'archon', 'administrador_base']);

// Obtener el ID del usuario y la empresa desde la sesión
$id_usuario = $_SESSION['usuario_id'];
$id_empresa = $_SESSION['id_empresa'];
$rol_usuario = $_SESSION['rol']; // Obtener el rol del usuario

// Consultar lista de proyectos asociados a la empresa del usuario
$sql_proyectos = "SELECT p.*, e.nombre AS empresa_nombre FROM Proyectos p 
                  LEFT JOIN Empresas e ON p.id_empresa = e.id 
                  WHERE p.id_empresa = ?";
$stmt_proyectos = $conn->prepare($sql_proyectos);
$stmt_proyectos->bind_param("i", $id_empresa);
$stmt_proyectos->execute();
$result_proyectos = $stmt_proyectos->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proyectos</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
    <!-- Incluir encabezado común -->
    <?php include '../includes/encabezado.php'; ?>

    <!-- Sección principal del panel de proyectos -->
    <section class="panel">
        <h2>Proyectos</h2>

        <!-- Contenedor de la tabla de proyectos -->
        <div class="table-container">
            <table class="tabla-proyectos">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Fecha de Inicio</th>
                        <th>Fecha de Fin</th>
                        <th>Estado</th>
                        <th>Empresa</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Verificar si hay proyectos disponibles
                    if ($result_proyectos->num_rows > 0) {
                        // Iterar sobre cada proyecto y mostrar sus detalles
                        while ($proyecto = $result_proyectos->fetch_assoc()) {
                            $estado_mensaje = '';

                            // Verificar si todas las tareas del proyecto tienen enlaces subidos
                            $sql_tareas = "SELECT t.id, la.id AS link_id 
                                           FROM Tareas t 
                                           LEFT JOIN Links_Archivos la ON t.id = la.id_tarea 
                                           WHERE t.id_proyecto = ?";
                            $stmt_tareas = $conn->prepare($sql_tareas);
                            $stmt_tareas->bind_param("i", $proyecto['id']);
                            $stmt_tareas->execute();
                            $result_tareas = $stmt_tareas->get_result();

                            $todas_tareas_con_enlaces = true;
                            while ($tarea = $result_tareas->fetch_assoc()) {
                                if (is_null($tarea['link_id'])) {
                                    $todas_tareas_con_enlaces = false;
                                    break;
                                }
                            }

                            // Solo mostrar los mensajes de estado si el proyecto no está completado
                            if ($proyecto['estado'] !== 'completado') {
                                if ($todas_tareas_con_enlaces && $result_tareas->num_rows > 0) {
                                    $estado_mensaje = "<span class='mensaje-espera-revision'>EN ESPERA DE REVISIÓN</span>";
                                } else {
                                    $fecha_fin = new DateTime($proyecto['fecha_fin']);
                                    $fecha_actual = new DateTime();
                                    $intervalo = $fecha_actual->diff($fecha_fin);

                                    if ($fecha_actual > $fecha_fin) {
                                        $estado_mensaje = "<span class='mensaje-urgente'>ESTE PROYECTO NO FUE COMPLETADO</span>";
                                    } else {
                                        if ($intervalo->days < 1) {
                                            if ($intervalo->h > 0) {
                                                $estado_mensaje = "<span class='mensaje-urgente'>QUEDAN " . $intervalo->h . " HORAS</span>";
                                            } elseif ($intervalo->i > 0) {
                                                $estado_mensaje = "<span class='mensaje-urgente'>QUEDAN " . $intervalo->i . " MINUTOS</span>";
                                            }
                                        } elseif ($intervalo->days <= 3) {
                                            $estado_mensaje = "<span class='mensaje-urgente'>QUEDAN " . $intervalo->days . " DÍAS</span>";
                                        }
                                    }
                                }
                            }

                            $id_proyecto = $proyecto['id'];
                            $nombre_proyecto = htmlspecialchars($proyecto['nombre']);
                            $descripcion_proyecto = htmlspecialchars($proyecto['descripcion']);
                            $fecha_inicio_proyecto = htmlspecialchars($proyecto['fecha_inicio']);
                            $fecha_fin_proyecto = htmlspecialchars($proyecto['fecha_fin']);
                            $estado_proyecto = htmlspecialchars($proyecto['estado']);
                            $nombre_empresa = htmlspecialchars($proyecto['empresa_nombre']);

                            echo "<tr>";
                            echo "<td>$nombre_proyecto</td>";
                            echo "<td>$descripcion_proyecto</td>";
                            echo "<td>$fecha_inicio_proyecto</td>";
                            echo "<td>$fecha_fin_proyecto</td>";
                            echo "<td>$estado_proyecto $estado_mensaje</td>";
                            echo "<td>$nombre_empresa</td>";
                            echo "<td>";
                            echo "<a href='detalles_proyecto.php?id=$id_proyecto' class='btn'>Ver Detalles</a>";
                            if ($rol_usuario !== 'usuario') {
                                echo "<a href='editar_proyecto.php?id=$id_proyecto' class='btn'>Editar</a>";
                                echo "<a href='eliminar_proyecto.php?id=$id_proyecto' onclick='return confirmarEliminacion()' class='btn'>Eliminar</a>";
                            }
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        // Mostrar mensaje si no hay proyectos disponibles
                        echo "<tr><td colspan='7'>No hay proyectos disponibles</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Incluir pie de página común -->
    <?php include '../includes/pie_pagina.php'; ?>
</body>
</html>

<!-- Script para confirmar la eliminación de un proyecto -->
<script>
function confirmarEliminacion() {
    return confirm('¿Estás seguro de que deseas eliminar este proyecto?');
}
</script>
