<?php
// Iniciar una nueva sesión o reanudar la existente si no está iniciada
session_start();
// Incluir funciones necesarias para verificar la sesión y el rol del usuario
require_once '../includes/funciones.php';

// Verificar si la sesión está iniciada y si el usuario tiene el rol adecuado (administrador o administrador_base)
verificarSesionIniciada(['administrador', 'administrador_base']);

// Incluir configuración de la base de datos
include '../includes/config.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <!-- Establecer la configuración de la ventana gráfica para asegurar que la página se vea bien en dispositivos móviles -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Título de la página -->
    <title>Panel de Control - Administrador</title>
    <!-- Enlace al archivo de estilos CSS -->
    <link rel="stylesheet" href="../css/estilos.css">
    <!-- Enlace al archivo de scripts JavaScript -->
    <script src="../js/scripts.js"></script>
</head>
<body>
    <!-- Incluir el encabezado común a todas las páginas -->
    <?php include '../includes/encabezado.php'; ?>

    <div class="panel">
        <!-- Mostrar mensaje de bienvenida con el nombre del usuario autenticado -->
        <h2>Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre']); ?></h2>
        <p>Panel de Control del Administrador</p>

        <!-- Sección para la gestión de proyectos -->
        <div class="panel-seccion">
            <h3>Gestión de Proyectos</h3>
            <ul>
                <!-- Enlace para ver los proyectos -->
                <li><a href="../proyectos/proyectos.php">Ver Proyectos</a></li>
                <!-- Enlace para crear un nuevo proyecto -->
                <li><a href="../proyectos/crear_proyecto.php">Crear Proyecto</a></li>
            </ul>
        </div>

        <!-- Sección para la gestión de tareas -->
        <div class="panel-seccion">
            <h3>Gestión de Tareas</h3>
            <ul>
                <!-- Enlace para ver las tareas -->
                <li><a href="../tareas/tareas.php">Ver Tareas</a></li>
                <!-- Enlace para crear una nueva tarea -->
                <li><a href="../tareas/crear_tarea.php">Crear Tarea</a></li>
            </ul>
        </div>

        <!-- Sección para la gestión de contactos empresariales -->
        <div class="panel-seccion">
            <h3>Gestión de Contactos Empresariales</h3>
            <ul>
                <!-- Enlace para ver los contactos empresariales -->
                <li><a href="../contactos/contactos.php">Ver Contactos</a></li>
                <!-- Enlace para crear un nuevo contacto empresarial -->
                <li><a href="../contactos/crear_contacto.php">Crear Contacto</a></li>
            </ul>
        </div>

        <!-- Sección para la gestión de usuarios -->
        <div class="panel-seccion">
            <h3>Gestión de Usuarios</h3>
            <ul>
                <!-- Enlace para ver los usuarios -->
                <li><a href="../usuarios/usuarios.php">Ver Usuarios</a></li>
                <!-- Enlace para crear un nuevo usuario -->
                <li><a href="../usuarios/crear_usuario.php">Crear Usuarios</a></li>
            </ul>
        </div>
    </div>

    <!-- Incluir el pie de página común a todas las páginas -->
    <?php include '../includes/pie_pagina.php'; ?>
</body>
</html>