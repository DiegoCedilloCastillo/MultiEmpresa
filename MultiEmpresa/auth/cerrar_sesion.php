<?php
session_start(); // Iniciar la sesión
session_unset(); // Destruir todas las variables de sesión
session_destroy(); // Destruir la sesión actual
header("Location: ../index.php"); // Redirigir al formulario de inicio de sesión
exit(); // Terminar la ejecución del script