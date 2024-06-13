<?php
// Iniciar una nueva sesión o reanudar la existente
session_start();
// Incluir funciones y configuraciones necesarias
require_once '../includes/funciones.php';
require_once '../includes/config.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario_id'])) {
    // Redirigir al formulario de inicio de sesión si no está autenticado
    header('Location: ../index.php');
    exit();
}

// Verificar si la solicitud es POST y si el ID de la notificación está presente
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_notificacion'])) {
    // Escapar el ID de la notificación para evitar inyecciones SQL
    $id_notificacion = $conn->real_escape_string($_POST['id_notificacion']);
    // Actualizar la notificación como leída en la base de datos
    $sql_marcar_leida = "UPDATE Notificaciones SET leida = TRUE WHERE id = $id_notificacion AND id_usuario = {$_SESSION['usuario_id']}";
    $conn->query($sql_marcar_leida);
}

// Redirigir de vuelta a la página de notificaciones
header('Location: notificaciones.php');
exit();