<?php
// Inicia una nueva sesión o reanuda la sesión existente
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
$sql_proyectos = "SELECT id, nombre FROM Proyectos WHERE id_empresa = ?";
$stmt_proyectos = $conn->prepare($sql_proyectos);
$stmt_proyectos->bind_param("i", $id_empresa);
$stmt_proyectos->execute();
$result_proyectos = $stmt_proyectos->get_result();

// Obtener las tareas según el proyecto seleccionado o todas las tareas si no se ha seleccionado un proyecto específico
$id_proyecto_seleccionado = isset($_GET['id_proyecto']) ? intval($_GET['id_proyecto']) : 0;
$sql_tareas = "SELECT t.*, p.nombre AS proyecto_nombre, u.nombre AS usuario_nombre 
               FROM Tareas t
               JOIN Proyectos p ON t.id_proyecto = p.id
               JOIN Usuarios u ON t.id_usuario = u.id
               WHERE p.id_empresa = ?";
if ($id_proyecto_seleccionado > 0) {
    // Filtrar tareas por el proyecto seleccionado
    $sql_tareas .= " AND t.id_proyecto = ?";
    $stmt_tareas = $conn->prepare($sql_tareas);
    $stmt_tareas->bind_param("ii", $id_empresa, $id_proyecto_seleccionado);
} else {
    // Obtener todas las tareas de la empresa
    $stmt_tareas = $conn->prepare($sql_tareas);
    $stmt_tareas->bind_param("i", $id_empresa);
}
$stmt_tareas->execute();
$result_tareas = $stmt_tareas->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tareas</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
    <!-- Incluir encabezado común -->
    <?php include '../includes/encabezado.php'; ?>

    <!-- Sección principal del panel de tareas -->
    <section class="panel">
        <h2>Tareas</h2>
        
        <!-- Formulario para seleccionar y filtrar tareas por proyecto -->
        <form method="GET" action="tareas.php">
            <label for="id_proyecto">Seleccionar Proyecto:</label>
            <select id="id_proyecto" name="id_proyecto" onchange="this.form.submit()">
                <option value="0">Todos los Proyectos</option>
                <?php
                // Mostrar la lista de proyectos en el dropdown
                while ($proyecto = $result_proyectos->fetch_assoc()) {
                    $selected = $id_proyecto_seleccionado == $proyecto['id'] ? 'selected' : '';
                    echo "<option value='{$proyecto['id']}' $selected>{$proyecto['nombre']}</option>";
                }
                ?>
            </select>
        </form>

        <!-- Contenedor de la tabla de tareas -->
        <div class="table-container">
            <table class="tabla-tareas">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Proyecto</th>
                        <th>Usuario Asignado</th>
                        <th>Fecha de Inicio</th>
                        <th>Fecha de Fin</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Verificar si hay tareas disponibles
                    if ($result_tareas->num_rows > 0) {
                        // Iterar sobre cada tarea y mostrar sus detalles
                        while ($tarea = $result_tareas->fetch_assoc()) {
                            $estado_mensaje = '';

                            // Verificar si la tarea tiene enlaces subidos
                            $sql_link = "SELECT COUNT(*) as total_links FROM Links_Archivos WHERE id_tarea = ?";
                            $stmt_link = $conn->prepare($sql_link);
                            $stmt_link->bind_param("i", $tarea['id']);
                            $stmt_link->execute();
                            $result_link = $stmt_link->get_result();
                            $link_data = $result_link->fetch_assoc();

                            if ($tarea['estado'] !== 'completada') {
                                if ($link_data['total_links'] > 0) {
                                    $estado_mensaje = "<span class='mensaje-espera-revision'>EN ESPERA DE REVISIÓN</span>";
                                } else {
                                    $fecha_fin = new DateTime($tarea['fecha_fin']);
                                    $fecha_actual = new DateTime();
                                    $intervalo = $fecha_actual->diff($fecha_fin);

                                    if ($fecha_actual > $fecha_fin) {
                                        $estado_mensaje = "<span class='mensaje-urgente'>ESTA TAREA NO FUE COMPLETADA</span>";
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

                            $id_tarea = $tarea['id'];
                            $nombre_tarea = htmlspecialchars($tarea['nombre']);
                            $descripcion_tarea = htmlspecialchars($tarea['descripcion']);
                            $fecha_inicio_tarea = htmlspecialchars($tarea['fecha_inicio']);
                            $fecha_fin_tarea = htmlspecialchars($tarea['fecha_fin']);
                            $estado_tarea = htmlspecialchars($tarea['estado']);
                            $nombre_proyecto = htmlspecialchars($tarea['proyecto_nombre']);
                            $nombre_usuario = htmlspecialchars($tarea['usuario_nombre']);

                            echo "<tr>";
                            echo "<td>$nombre_tarea</td>";
                            echo "<td>$descripcion_tarea</td>";
                            echo "<td>$nombre_proyecto</td>";
                            echo "<td>$nombre_usuario</td>";
                            echo "<td>$fecha_inicio_tarea</td>";
                            echo "<td>$fecha_fin_tarea</td>";
                            echo "<td>$estado_tarea $estado_mensaje</td>";
                            echo "<td>";
                            echo "<a href='detalles_tarea.php?id=$id_tarea' class='btn'>Ver Detalles</a>";
                            if ($rol_usuario !== 'usuario') {
                                echo "<a href='editar_tarea.php?id=$id_tarea' class='btn'>Editar</a>";
                                echo "<a href='eliminar_tarea.php?id=$id_tarea' onclick='return confirmarEliminacion()' class='btn'>Eliminar</a>";
                            }
                            echo "<a href='../links/links_tareas.php?id_tarea=$id_tarea' class='btn'>Ver Links</a>";
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        // Mostrar mensaje si no hay tareas disponibles
                        echo "<tr><td colspan='8'>No hay tareas disponibles</td></tr>";
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

<!-- Script para confirmar la eliminación de una tarea -->
<script>
function confirmarEliminacion() {
    return confirm('¿Estás seguro de que deseas eliminar esta tarea?');
}
</script>