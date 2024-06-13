<?php
// Iniciar una nueva sesión o reanudar la existente
session_start();
// Incluir configuración de la base de datos y funciones necesarias
require_once '../includes/config.php';
require_once '../includes/funciones.php';

// Verificar si la sesión está iniciada y si el usuario tiene el rol adecuado
verificarSesionIniciada(['administrador', 'gerente', 'usuario', 'archon', 'administrador_base']);

// Consulta SQL para obtener la lista de usuarios dependiendo del rol
if ($_SESSION['rol'] === 'archon') {
    // Si el usuario es Archon, obtener todos los usuarios de todas las empresas
    $sql_usuarios = "SELECT U.*, E.nombre AS empresa_nombre 
                     FROM usuarios U
                     LEFT JOIN empresas E ON U.id_empresa = E.id";
} else {
    // Si el usuario no es Archon, obtener los usuarios solo de la empresa del usuario actual
    $id_empresa = $_SESSION['id_empresa'];
    $sql_usuarios = "SELECT U.*, E.nombre AS empresa_nombre 
                     FROM usuarios U
                     LEFT JOIN empresas E ON U.id_empresa = E.id
                     WHERE U.id_empresa = ?";
}

// Preparar y ejecutar la consulta
$stmt_usuarios = $conn->prepare($sql_usuarios);
if ($_SESSION['rol'] !== 'archon') {
    $stmt_usuarios->bind_param("i", $id_empresa);
}
$stmt_usuarios->execute();
$result_usuarios = $stmt_usuarios->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
    <!-- Incluir el encabezado común a todas las páginas -->
    <?php include '../includes/encabezado.php'; ?>

    <!-- Contenedor principal del panel -->
    <div class="panel">
        <h2>Gestión de Usuarios</h2>
        <!-- Contenedor de la tabla -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Apellido</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Empresa</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result_usuarios->num_rows > 0) {
                        while ($fila = $result_usuarios->fetch_assoc()) {
                            // Determinar el color del texto si el usuario es un administrador base
                            $color = ($fila['rol'] == 'administrador_base') ? 'style="color:blue"' : '';
                            echo "<tr>
                                    <td $color>" . htmlspecialchars($fila['nombre']) . "</td>
                                    <td>" . htmlspecialchars($fila['apellido']) . "</td>
                                    <td>" . htmlspecialchars($fila['email']) . "</td>
                                    <td>" . htmlspecialchars($fila['rol']) . "</td>
                                    <td>" . htmlspecialchars($fila['empresa_nombre']) . "</td>
                                    <td>";

                            // Determinar las acciones permitidas para el usuario actual
                            $rol_usuario_actual = $_SESSION['rol'];
                            $deshabilitar_editar = false;
                            $deshabilitar_eliminar = false;

                            if ($rol_usuario_actual === 'usuario') {
                                $deshabilitar_editar = true;
                                $deshabilitar_eliminar = true;
                            } else if ($rol_usuario_actual === 'gerente') {
                                if ($fila['rol'] === 'administrador' || $fila['rol'] === 'administrador_base' || $fila['id'] == $_SESSION['usuario_id']) {
                                    $deshabilitar_editar = true;
                                    $deshabilitar_eliminar = true;
                                }
                            } else if ($rol_usuario_actual === 'administrador') {
                                if ($fila['rol'] === 'administrador_base' || $fila['id'] == $_SESSION['usuario_id']) {
                                    $deshabilitar_editar = true;
                                    $deshabilitar_eliminar = true;
                                }
                            }

                            // Mostrar botones de edición y eliminación según permisos
                            if ($deshabilitar_editar) {
                                echo "<button disabled class='btn-disabled'>Editar</button>";
                            } else {
                                echo "<a href='editar_usuario.php?id=" . htmlspecialchars($fila['id']) . "' class='btn'>Editar</a>";
                            }

                            if ($deshabilitar_eliminar) {
                                echo "<button disabled class='btn-disabled'>Eliminar</button>";
                            } else {
                                echo "<a href='eliminar_usuario.php?id=" . htmlspecialchars($fila['id']) . "' onclick='return confirmarEliminacion()' class='btn'>Eliminar</a>";
                            }

                            echo "</td>
                                  </tr>";
                        }
                    } else {
                        // Mensaje en caso de no haber usuarios registrados
                        echo "<tr><td colspan='6'>No hay usuarios registrados.</td></tr>";
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

<!-- Script para confirmar la eliminación de un usuario -->
<script>
function confirmarEliminacion() {
    return confirm('¿Estás seguro de que deseas eliminar este usuario?');
}
</script>