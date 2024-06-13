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

// Incluir configuración de base de datos
include '../includes/config.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <!-- Establecer la configuración de la ventana gráfica para asegurar que la página se vea bien en dispositivos móviles -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Título de la página -->
    <title>Panel de Control - Archon</title>
    <!-- Enlace al archivo de estilos CSS -->
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
    <!-- Incluir el encabezado común a todas las páginas -->
    <?php include '../includes/encabezado.php'; ?>

    <div class="panel">
        <!-- Mostrar mensaje de bienvenida con el nombre del usuario autenticado -->
        <h2>Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre']); ?></h2>
        <p>Panel de Control del Archon</p>

        <!-- Sección para la gestión de empresas -->
        <div class="panel-seccion">
            <h3>Gestión de Empresas</h3>
            <ul>
                <!-- Enlace para ver las empresas -->
                <li><a href="../empresas/empresas.php">Ver Empresas</a></li>
                <!-- Enlace para crear una nueva empresa -->
                <li><a href="../empresas/crear_empresa.php">Crear Empresa</a></li>
            </ul>
        </div>

        <!-- Sección para la gestión de usuarios -->
        <div class="panel-seccion">
            <h3>Gestión de Usuarios</h3>
            <ul>
                <!-- Enlace para ver los usuarios con un parámetro adicional para indicar que el usuario es un archon -->
                <li><a href="../usuarios/usuarios.php?archon=1">Ver Usuarios</a></li>
            </ul>
        </div>

        <!-- Sección para la gestión de otros Archons -->
        <div class="panel-seccion">
            <h3>Gestión de Archons</h3>
            <ul>
                <!-- Enlace para ver los Archons -->
                <li><a href="../archons/archons.php">Ver Archons</a></li>
                <!-- Enlace para crear un nuevo Archon -->
                <li><a href="../archons/crear_archon.php">Crear Archon</a></li>
            </ul>
        </div>
    </div>

    <!-- Incluir el pie de página común a todas las páginas -->
    <?php include '../includes/pie_pagina.php'; ?>
</body>
</html>