<?php
// Iniciar una nueva sesión o reanudar la existente
session_start();

// Incluir los archivos necesarios
require_once '../includes/funciones.php';
require_once '../includes/config.php';
require_once '../notificaciones/funciones_notificaciones.php';

// Verificar que la sesión esté iniciada y que el rol sea permitido
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'], ['administrador', 'gerente', 'administrador_base'])) {
    header('Location: ../index.php');
    exit();
}

// Obtener el ID de la tarea a editar desde la URL y sanitizarlo
$id_tarea = $conn->real_escape_string($_GET['id']);

// Obtener datos de la tarea desde la base de datos
$sql_tarea = "SELECT * FROM Tareas WHERE id = $id_tarea";
$result_tarea = $conn->query($sql_tarea);

if ($result_tarea->num_rows == 0) {
    echo "La tarea no existe.";
    exit();
}

$tarea = $result_tarea->fetch_assoc();

// Verificar si se envió el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener y sanitizar los datos del formulario
    $nombre_tarea = $conn->real_escape_string($_POST['nombre_tarea']);
    $descripcion = $conn->real_escape_string($_POST['descripcion']);
    $fecha_inicio = $conn->real_escape_string($_POST['fecha_inicio']);
    $fecha_fin = $conn->real_escape_string($_POST['fecha_fin']);
    $estado = $conn->real_escape_string($_POST['estado']);
    $id_proyecto = $conn->real_escape_string($_POST['id_proyecto']);
    $id_usuario = $conn->real_escape_string($_POST['id_usuario']);

    // Evitar cambiar el estado a pendiente si no era el estado original
    if ($estado === 'pendiente' && $tarea['estado'] !== 'pendiente') {
        $estado = $tarea['estado'];
    }

    // Verificar que las fechas estén dentro del rango del proyecto
    $sql_proyecto = "SELECT fecha_inicio, fecha_fin FROM Proyectos WHERE id = $id_proyecto";
    $result_proyecto = $conn->query($sql_proyecto);
    $proyecto = $result_proyecto->fetch_assoc();

    if ($fecha_inicio < $proyecto['fecha_inicio'] || $fecha_fin > $proyecto['fecha_fin']) {
        // Mostrar un error si las fechas no están dentro del rango del proyecto
        $error = "Las fechas deben estar dentro del rango del proyecto seleccionado.";
    } else {
        // Actualizar los datos de la tarea en la base de datos
        $sql = "UPDATE Tareas SET nombre='$nombre_tarea', descripcion='$descripcion', fecha_inicio='$fecha_inicio', fecha_fin='$fecha_fin', estado='$estado', id_proyecto='$id_proyecto', id_usuario='$id_usuario' WHERE id=$id_tarea";
        
        if ($conn->query($sql) === TRUE) {
            // Obtener el id_general de la tarea
            $id_general = $tarea['id_general'];

            // Insertar en Historial_Actividades
            $sql_historial = "INSERT INTO historial_actividades (id_general, id_entidad, tipo_entidad, descripcion, estado, usuario_asignado, usuario_modifico, id_empresa)
                              VALUES (?, ?, 'tarea', ?, ?, ?, ?, ?)";
            $stmt_historial = $conn->prepare($sql_historial);
            $stmt_historial->bind_param("iissiii", $id_general, $id_tarea, $nombre_tarea, $estado, $id_usuario, $_SESSION['usuario_id'], $_SESSION['id_empresa']);
            $stmt_historial->execute();

            // Crear una notificación para el usuario asignado
            $nombre_usuario = $_SESSION['nombre'];
            $accion = "$nombre_usuario actualizó los datos de tu tarea: $nombre_tarea";
            crearNotificacionUsuario($conn, $id_usuario, $accion);

            // Redirigir a la página de tareas con un mensaje de éxito
            $_SESSION['mensaje'] = "Tarea actualizada exitosamente.";
            header("Location: tareas.php");
            exit();
        } else {
            // Mostrar un error si falla la actualización en la base de datos
            $error = "Error: " . $sql . "<br>" . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Tarea</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
    <section class="form-container">
        <h2>Editar Tarea</h2>
        <?php if (isset($error)) : ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <!-- Formulario para editar una tarea existente -->
        <form action="editar_tarea.php?id=<?= $id_tarea ?>" method="POST">
            <label for="nombre_tarea">Nombre de la Tarea</label>
            <input type="text" id="nombre_tarea" name="nombre_tarea" value="<?= htmlspecialchars($tarea['nombre']) ?>" required>
            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion" required><?= htmlspecialchars($tarea['descripcion']) ?></textarea>
            <label for="fecha_inicio">Fecha de Inicio</label>
            <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?= $tarea['fecha_inicio'] ?>" required>
            <label for="fecha_fin">Fecha de Fin</label>
            <input type="date" id="fecha_fin" name="fecha_fin" value="<?= $tarea['fecha_fin'] ?>" required>
            <label for="estado">Estado</label>
            <select id="estado" name="estado">
                <option value="pendiente" <?= $tarea['estado'] === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                <option value="en progreso" <?= $tarea['estado'] === 'en progreso' ? 'selected' : '' ?>>En progreso</option>
                <option value="completada" <?= $tarea['estado'] === 'completada' ? 'selected' : '' ?>>Completada</option>
            </select>
            <label for="id_proyecto">Proyecto</label>
            <select id="id_proyecto" name="id_proyecto" required onchange="cargarFechasProyecto(this.value)">
                <?php
                // Obtener los proyectos de la empresa del usuario actual
                $sql_proyectos = "SELECT id, nombre, fecha_inicio, fecha_fin FROM Proyectos WHERE id_empresa = " . $_SESSION['id_empresa'];
                $result_proyectos = $conn->query($sql_proyectos);
                if ($result_proyectos->num_rows > 0) {
                    // Mostrar los proyectos en el menú desplegable
                    while ($proyecto = $result_proyectos->fetch_assoc()) {
                        $selected = $proyecto['id'] == $tarea['id_proyecto'] ? 'selected' : '';
                        echo "<option value='" . $proyecto['id'] . "' data-fecha-inicio='" . $proyecto['fecha_inicio'] . "' data-fecha-fin='" . $proyecto['fecha_fin'] . "' $selected>" . htmlspecialchars($proyecto['nombre']) . "</option>";
                    }
                } else {
                    echo "<option value=''>No hay proyectos disponibles</option>";
                }
                ?>
            </select>
            <label for="id_usuario">Asignar a Usuario</label>
            <select id="id_usuario" name="id_usuario" required>
                <?php
                // Obtener los usuarios de la empresa del usuario actual
                $sql_usuarios = "SELECT id, nombre FROM Usuarios WHERE id_empresa = " . $_SESSION['id_empresa'];
                $result_usuarios = $conn->query($sql_usuarios);
                if ($result_usuarios->num_rows > 0) {
                    // Mostrar los usuarios en el menú desplegable
                    while ($usuario = $result_usuarios->fetch_assoc()) {
                        $selected = $usuario['id'] == $tarea['id_usuario'] ? 'selected' : '';
                        echo "<option value='" . $usuario['id'] . "' $selected>" . htmlspecialchars($usuario['nombre']) . "</option>";
                    }
                } else {
                    echo "<option value=''>No hay usuarios disponibles</option>";
                }
                ?>
            </select>
            <button type="submit">Actualizar Tarea</button>
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
</body>
</html>