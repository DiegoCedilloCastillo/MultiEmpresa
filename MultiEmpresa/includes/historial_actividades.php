<?php
// Inicia una nueva sesión o reanuda la existente
session_start();
// Incluye funciones y configuración necesarias
require_once 'funciones.php';
require_once 'config.php';

// Verifica si el usuario está autenticado
if (!isset($_SESSION['usuario_id'])) {
    // Redirige al formulario de inicio de sesión si no está autenticado
    header('Location: ../index.php');
    exit();
}

// Obtiene el ID de la empresa del usuario actual
$id_empresa = $_SESSION['id_empresa'];
// Obtiene los filtros de proyecto y usuario si están establecidos
$filtro_proyecto = isset($_GET['proyecto']) ? $conn->real_escape_string($_GET['proyecto']) : '';
$filtro_usuario = isset($_GET['usuario']) ? $conn->real_escape_string($_GET['usuario']) : '';

// Consulta principal para obtener el historial de actividades
$sql_historial = "SELECT ha.*, 
                  u.nombre AS nombre_usuario_modifico, 
                  ua.nombre AS nombre_usuario_asignado, 
                  p.nombre AS nombre_proyecto,
                  t.nombre AS nombre_tarea
                  FROM Historial_Actividades ha
                  LEFT JOIN Usuarios u ON ha.usuario_modifico = u.id
                  LEFT JOIN Usuarios ua ON ha.usuario_asignado = ua.id
                  LEFT JOIN Proyectos p ON ha.id_entidad = p.id AND ha.tipo_entidad = 'proyecto'
                  LEFT JOIN Tareas t ON ha.id_entidad = t.id AND ha.tipo_entidad = 'tarea'
                  WHERE u.id_empresa = ? AND (ha.tipo_entidad = 'proyecto' OR ha.tipo_entidad = 'tarea')";

// Inicializa los parámetros para la consulta
$params = [$id_empresa];
$types = "i";

// Agrega filtros a la consulta si están establecidos
if ($filtro_proyecto) {
    $sql_historial .= " AND ha.id_entidad = ?";
    $params[] = $filtro_proyecto;
    $types .= "i";
}

if ($filtro_usuario) {
    $sql_historial .= " AND ha.usuario_asignado = ?";
    $params[] = $filtro_usuario;
    $types .= "i";
}

// Ordena los resultados por fecha de modificación descendente
$sql_historial .= " ORDER BY ha.fecha_modificacion DESC";

// Prepara y ejecuta la consulta del historial de actividades
$stmt_historial = $conn->prepare($sql_historial);
$stmt_historial->bind_param($types, ...$params);
$stmt_historial->execute();
$result_historial = $stmt_historial->get_result();

// Consulta para obtener la lista de proyectos
$sql_proyectos = "SELECT id, nombre FROM Proyectos WHERE id_empresa = ?";
$stmt_proyectos = $conn->prepare($sql_proyectos);
$stmt_proyectos->bind_param("i", $id_empresa);
$stmt_proyectos->execute();
$result_proyectos = $stmt_proyectos->get_result();

// Consulta para obtener la lista de usuarios
$sql_usuarios = "SELECT id, nombre FROM Usuarios WHERE id_empresa = ?";
$stmt_usuarios = $conn->prepare($sql_usuarios);
$stmt_usuarios->bind_param("i", $id_empresa);
$stmt_usuarios->execute();
$result_usuarios = $stmt_usuarios->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Actividades</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
    <!-- Incluye el archivo del encabezado común a todas las páginas -->
    <?php include 'encabezado.php'; ?>

    <section class="panel">
        <h2>Historial de Actividades</h2>
        <!-- Formulario para filtrar el historial de actividades -->
        <form method="GET" action="historial_actividades.php">
            <label for="proyecto">Filtrar por Proyecto</label>
            <select id="proyecto" name="proyecto">
                <option value="">Todos los proyectos</option>
                <!-- Llenar el select con los proyectos obtenidos de la base de datos -->
                <?php while ($proyecto = $result_proyectos->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($proyecto['id']) ?>" <?= $filtro_proyecto == $proyecto['id'] ? 'selected' : '' ?>><?= htmlspecialchars($proyecto['nombre']) ?></option>
                <?php endwhile; ?>
            </select>
            <label for="usuario">Filtrar por Usuario</label>
            <select id="usuario" name="usuario">
                <option value="">Todos los usuarios</option>
                <!-- Llenar el select con los usuarios obtenidos de la base de datos -->
                <?php while ($usuario = $result_usuarios->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($usuario['id']) ?>" <?= $filtro_usuario == $usuario['id'] ? 'selected' : '' ?>><?= htmlspecialchars($usuario['nombre']) ?></option>
                <?php endwhile; ?>
            </select>
            <button type="submit" class="btn">Filtrar</button>
        </form>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Entidad</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th>Asignado a</th>
                        <th>Modificado por</th>
                        <th>Fecha de Modificación</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Llenar la tabla con los resultados del historial de actividades -->
                    <?php if ($result_historial->num_rows > 0): ?>
                        <?php while ($actividad = $result_historial->fetch_assoc()): ?>
                            <tr>
                                <td><?= $actividad['tipo_entidad'] === 'tarea' ? 'Tarea' : 'Proyecto' ?></td>
                                <td><?= htmlspecialchars($actividad['tipo_entidad'] === 'tarea' ? $actividad['nombre_tarea'] : $actividad['nombre_proyecto']) ?></td>
                                <td><?= htmlspecialchars($actividad['descripcion']) ?></td>
                                <td><?= htmlspecialchars($actividad['estado']) ?></td>
                                <td><?= htmlspecialchars($actividad['nombre_usuario_asignado']) ?></td>
                                <td><?= htmlspecialchars($actividad['nombre_usuario_modifico']) ?></td>
                                <td><?= htmlspecialchars($actividad['fecha_modificacion']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">No hay actividades registradas.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Incluye el archivo del pie de página común a todas las páginas -->
    <?php include 'pie_pagina.php'; ?>
</body>
</html>

<?php
// Cerrar las declaraciones preparadas y la conexión a la base de datos
$stmt_historial->close();
$stmt_proyectos->close();
$stmt_usuarios->close();
$conn->close();
?>