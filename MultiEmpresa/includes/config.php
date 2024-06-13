<?php
// Parámetros de configuración para la base de datos
$servidor = "localhost"; // Dirección del servidor de base de datos
$usuario = "root"; // Nombre de usuario de la base de datos
$contrasena = ""; // Contraseña del usuario de la base de datos
$baseDeDatos = "multiempresa"; // Nombre de la base de datos
$puerto = 3306; // Puerto para MySQL

// Crear la conexión usando MySQLi
$conn = new mysqli($servidor, $usuario, $contrasena, $baseDeDatos, $puerto);

// Verificar la conexión
if ($conn->connect_error) {
    // Mostrar un mensaje de error y detener el script si la conexión falla
    die("Conexión fallida: " . $conn->connect_error);
}