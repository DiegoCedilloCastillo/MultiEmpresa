<?php
// Iniciar una nueva sesión o reanudar la existente
session_start();

// Incluir los archivos necesarios
require_once '../includes/funciones.php';
require_once '../notificaciones/funciones_notificaciones.php';

// Verificar que la sesión esté iniciada y que el rol sea permitido
verificarSesionIniciada(['administrador', 'gerente', 'archon', 'administrador_base']);

// Incluir la configuración de la base de datos
include '../includes/config.php';

// Obtener la empresa del usuario actual
$id_empresa = $_SESSION['id_empresa'];

// Verificar si se envió el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener y sanitizar los datos del formulario
    $nombre_tarea = $conn->real_escape_string($_POST['nombre_tarea']);
    $descripcion = $conn->real_escape_string($_POST['descripcion']);
    $fecha_inicio = $conn->real_escape_string($_POST['fecha_inicio']);
    $fecha_fin = $conn->real_escape_string($_POST['fecha_fin']);
    $estado = 'pendiente'; // Estado inicial por defecto
    $id_proyecto = $conn->real_escape_string($_POST['id_proyecto']);
    $id_usuario = $conn->real_escape_string($_POST['id_usuario']);

    // Verificar que las fechas estén dentro del rango del proyecto
    $sql_proyecto = "SELECT fecha_inicio, fecha_fin FROM Proyectos WHERE id = $id_proyecto";
    $result_proyecto = $conn->query($sql_proyecto);
    $proyecto = $result_proyecto->fetch_assoc();

    if ($fecha_inicio < $proyecto['fecha_inicio'] || $fecha_fin > $proyecto['fecha_fin']) {
        // Mostrar un error si las fechas no están dentro del rango del proyecto
        $error = "Las fechas deben estar dentro del rango del proyecto seleccionado.";
    } else {
        // Generar un ID general para la tarea
        $id_general = generar_id($conn, 'tarea');

        if ($id_general) {
            // Preparar la consulta para insertar la nueva tarea en la base de datos
            $sql_tarea = "INSERT INTO Tareas (id_general, nombre, descripcion, fecha_inicio, fecha_fin, estado, id_proyecto, id_usuario, id_empresa)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql_tarea);
            $stmt->bind_param("isssssiii", $id_general, $nombre_tarea, $descripcion, $fecha_inicio, $fecha_fin, $estado, $id_proyecto, $id_usuario, $id_empresa);

            if ($stmt->execute()) {
                $id_tarea = $stmt->insert_id; // Obtener la ID específica generada automáticamente

                // Insertar en Historial_Actividades
                $sql_historial = "INSERT INTO historial_actividades (id_general, id_entidad, tipo_entidad, descripcion, estado, usuario_asignado, usuario_modifico, id_empresa)
                                  VALUES (?, ?, 'tarea', ?, ?, ?, ?, ?)";
                $stmt_historial = $conn->prepare($sql_historial);
                $stmt_historial->bind_param("iissiii", $id_general, $id_tarea, $nombre_tarea, $estado, $id_usuario, $_SESSION['usuario_id'], $id_empresa);
                $stmt_historial->execute();

                // Crear una notificación para el usuario asignado
                $usuario_actual = $_SESSION['usuario_id'];
                $nombre_usuario_actual = $_SESSION['nombre'];
                $accion = "$nombre_usuario_actual te ha asignado una nueva tarea: $nombre_tarea";
                crearNotificacionUsuario($conn, $id_usuario, $accion);

                // Redirigir a la página de tareas con un mensaje de éxito
                $_SESSION['mensaje'] = "Tarea creada exitosamente.";
                header("Location: tareas.php");
                exit();
            } else {
                // Mostrar un error si falla la inserción en la base de datos
                $error = "Error: " . $stmt->error;
                // Eliminar la entrada en IDs si falla la inserción
                $conn->query("DELETE FROM ids WHERE id = $id_general");
            }
        } else {
            $error = "Error: no se pudo generar el ID general.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Tarea</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
    <!-- Incluir el encabezado -->
    <?php include '../includes/encabezado.php'; ?>

    <section class="form-container">
        <h2>Crear Tarea</h2>
        <?php if (isset($error)) : ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <!-- Formulario para crear una nueva tarea -->
        <form action="crear_tarea.php" method="POST">
            <label for="nombre_tarea">Nombre de la Tarea</label>
            <input type="text" id="nombre_tarea" name="nombre_tarea" required>
            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion" required></textarea>
            <label for="id_proyecto">Proyecto</label>
            <select id="id_proyecto" name="id_proyecto" required onchange="cargarFechasProyecto(this.value)">
                <?php
                // Obtener los proyectos de la empresa del usuario actual
                $sql_proyectos = "SELECT id, nombre, fecha_inicio, fecha_fin FROM Proyectos WHERE id_empresa = $id_empresa";
                $result_proyectos = $conn->query($sql_proyectos);
                if ($result_proyectos->num_rows > 0) {
                    // Mostrar los proyectos en el menú desplegable
                    while ($proyecto = $result_proyectos->fetch_assoc()) {
                        echo "<option value='" . $proyecto['id'] . "' data-fecha-inicio='" . $proyecto['fecha_inicio'] . "' data-fecha-fin='" . $proyecto['fecha_fin'] . "'>" . htmlspecialchars($proyecto['nombre']) . "</option>";
                    }
                } else {
                    echo "<option value=''>No hay proyectos disponibles</option>";
                }
                ?>
            </select>
            <label for="fecha_inicio">Fecha de Inicio</label>
            <input type="date" id="fecha_inicio" name="fecha_inicio" required>
            <label for="fecha_fin">Fecha de Fin</label>
            <input type="date" id="fecha_fin" name="fecha_fin" required>
            <label for="id_usuario">Asignar a Usuario</label>
            <select id="id_usuario" name="id_usuario" required>
                <?php
                // Obtener los usuarios de la empresa del usuario actual
                $sql_usuarios = "SELECT id, nombre FROM Usuarios WHERE id_empresa = $id_empresa";
                $result_usuarios = $conn->query($sql_usuarios);
                if ($result_usuarios->num_rows > 0) {
                    // Mostrar los usuarios en el menú desplegable
                    while ($usuario = $result_usuarios->fetch_assoc()) {
                        echo "<option value='" . $usuario['id'] . "'>" . htmlspecialchars($usuario['nombre']) . "</option>";
                    }
                } else {
                    echo "<option value=''>No hay usuarios disponibles</option>";
                }
                ?>
            </select>
            <button type="submit">Crear Tarea</button>
        </form>
    </section>

    <script>
    // Función para ajustar las fechas según el proyecto seleccionado
    function cargarFechasProyecto(idProyecto) {
        var selectProyecto = document.getElementById('id_proyecto');
        var optionSeleccionada = selectProyecto.options[selectProyecto.selectedIndex];
        var fechaInicioProyecto = optionSeleccionada.getAttribute('data-fecha-inicio');
        var fechaFinProyecto = optionSeleccionada.getAttribute('data-fecha-fin');
        document.getElementById('fecha_inicio').setAttribute('min', fechaInicioProyecto);
        document.getElementById('fecha_inicio').setAttribute('max', fechaFinProyecto);
        document.getElementById('fecha_fin').setAttribute('min', fechaInicioProyecto);
        document.getElementById('fecha_fin').setAttribute('max', fechaFinProyecto);
    }
    </script>

    <!-- Incluir el pie de página -->
    <?php include '../includes/pie_pagina.php'; ?>
</body>
</html>