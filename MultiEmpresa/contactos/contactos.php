<?php
// Iniciar una nueva sesión o reanudar la existente
session_start();
// Incluir configuración de la base de datos y funciones necesarias
require_once '../includes/config.php';
require_once '../includes/funciones.php';

// Verificar si la sesión está iniciada y si el usuario tiene uno de los roles permitidos
verificarSesionIniciada(['administrador', 'gerente', 'usuario', 'archon', 'administrador_base']);

// Construir la consulta SQL según el rol del usuario
if ($_SESSION['rol'] === 'archon') {
    // Si el usuario es Archon, obtener todos los contactos
    $sql_contactos = "SELECT C.*, E.nombre AS empresa_nombre 
                      FROM contactos C
                      LEFT JOIN empresas E ON C.id_empresa = E.id";
} else {
    // Si el usuario no es Archon, obtener solo los contactos de su empresa
    $id_empresa = $_SESSION['id_empresa'];
    $sql_contactos = "SELECT C.*, E.nombre AS empresa_nombre 
                      FROM contactos C
                      LEFT JOIN empresas E ON C.id_empresa = E.id
                      WHERE C.id_empresa = ?";
}

// Preparar la consulta SQL
$stmt_contactos = $conn->prepare($sql_contactos);
if ($_SESSION['rol'] !== 'archon') {
    // Si el usuario no es Archon, enlazar el ID de la empresa a la consulta
    $stmt_contactos->bind_param("i", $id_empresa);
}
$stmt_contactos->execute();
$result_contactos = $stmt_contactos->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <!-- Establecer la configuración de la ventana gráfica para asegurar que la página se vea bien en dispositivos móviles -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Título de la página -->
    <title>Gestión de Contactos</title>
    <!-- Enlace al archivo de estilos CSS -->
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
    <!-- Incluir el encabezado común a todas las páginas -->
    <?php include '../includes/encabezado.php'; ?>

    <div class="panel">
        <h2>Gestión de Contactos</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Empresa</th>
                        <th>Teléfono</th>
                        <th>Email</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Verificar si hay contactos registrados
                    if ($result_contactos->num_rows > 0) {
                        // Iterar sobre cada contacto y mostrar sus datos en la tabla
                        while ($fila = $result_contactos->fetch_assoc()) {
                            echo "<tr>
                                    <td>" . htmlspecialchars($fila['nombre']) . "</td>
                                    <td>" . htmlspecialchars($fila['tipo']) . "</td>
                                    <td>" . htmlspecialchars($fila['empresa_nombre']) . "</td>
                                    <td>" . htmlspecialchars($fila['telefono']) . "</td>
                                    <td>" . htmlspecialchars($fila['email']) . "</td>
                                    <td>";

                            $rol_usuario_actual = $_SESSION['rol'];
                            $deshabilitar_editar = false;
                            $deshabilitar_eliminar = false;

                            if ($rol_usuario_actual === 'usuario') {
                                // Si el usuario es 'usuario', deshabilitar edición y eliminación
                                $deshabilitar_editar = true;
                                $deshabilitar_eliminar = true;
                            } elseif ($rol_usuario_actual === 'gerente') {
                                // Verificar que los gerentes no puedan eliminar contactos de ciertas categorías
                                if ($_SESSION['rol'] === 'administrador' || $_SESSION['rol'] === 'administrador_base') {
                                    $deshabilitar_editar = true;
                                    $deshabilitar_eliminar = true;
                                }
                            } elseif ($rol_usuario_actual === 'administrador') {
                                if ($_SESSION['rol'] === 'administrador_base') {
                                    $deshabilitar_editar = true;
                                    $deshabilitar_eliminar = true;
                                }
                            }

                            if ($deshabilitar_editar) {
                                echo "<button disabled class='btn-disabled'>Editar</button>";
                            } else {
                                echo "<a href='editar_contacto.php?id=" . htmlspecialchars($fila['id']) . "' class='btn'>Editar</a>";
                            }

                            if ($deshabilitar_eliminar) {
                                echo "<button disabled class='btn-disabled'>Eliminar</button>";
                            } else {
                                echo "<a href='eliminar_contacto.php?id=" . htmlspecialchars($fila['id']) . "' onclick='return confirmarEliminacion()' class='btn'>Eliminar</a>";
                            }

                            echo "</td>
                                  </tr>";
                        }
                    } else {
                        // Mostrar un mensaje si no hay contactos registrados
                        echo "<tr><td colspan='6'>No hay contactos registrados.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <?php if ($_SESSION['rol'] !== 'usuario'): ?>
            <!-- Mostrar el botón de crear contacto si el usuario no es 'usuario' -->
            <a href="crear_contacto.php" class="btn-create">Crear Contacto</a>
        <?php endif; ?>
    </div>

    <!-- Incluir el pie de página común a todas las páginas -->
    <?php include '../includes/pie_pagina.php'; ?>
</body>
</html>

<script>
// Función para confirmar la eliminación de un contacto
function confirmarEliminacion() {
    return confirm('¿Estás seguro de que deseas eliminar este contacto?');
}
</script>