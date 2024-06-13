<?php
// Inicia una nueva sesión o reanuda la existente
session_start();
// Incluye funciones y configuraciones necesarias
require_once '../includes/funciones.php';
include '../includes/config.php';

// Verifica si el método de la solicitud es POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Escapa los datos del formulario para prevenir inyecciones SQL
    $email = $conn->real_escape_string($_POST['email']);
    $password = $conn->real_escape_string($_POST['password']);

    // Consulta para verificar las credenciales en la tabla de Usuarios
    $sql_usuario = "SELECT U.*, E.nombre AS empresa_nombre 
                    FROM Usuarios U
                    JOIN Empresas E ON U.id_empresa = E.id
                    WHERE U.email = ?";
    $stmt_usuario = $conn->prepare($sql_usuario);
    $stmt_usuario->bind_param("s", $email);
    $stmt_usuario->execute();
    $result_usuario = $stmt_usuario->get_result();

    // Verifica si se encontró un usuario con el email proporcionado
    if ($result_usuario->num_rows == 1) {
        $usuario = $result_usuario->fetch_assoc();

        if (password_verify($password, $usuario['password'])) {
            // Establece variables de sesión para el usuario
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['nombre'] = $usuario['nombre'];
            $_SESSION['rol'] = $usuario['rol'];
            $_SESSION['id_empresa'] = $usuario['id_empresa'];
            $_SESSION['empresa_nombre'] = $usuario['empresa_nombre']; // Añadir nombre de la empresa

            // Redirige al panel de control correspondiente
            redirigirPanel();
        } else {
            // Contraseña incorrecta
            $_SESSION['error'] = 'Contraseña incorrecta.';
        }
    } else {
        // Si no se encuentra el usuario en la tabla de Usuarios, busca en la tabla de Archons
        $sql_archon = "SELECT * FROM Archons WHERE email = ?";
        $stmt_archon = $conn->prepare($sql_archon);
        $stmt_archon->bind_param("s", $email);
        $stmt_archon->execute();
        $result_archon = $stmt_archon->get_result();

        // Verifica si se encontró un Archon con el email proporcionado
        if ($result_archon->num_rows == 1) {
            $archon = $result_archon->fetch_assoc();
            if (password_verify($password, $archon['password'])) {
                // Establece variables de sesión para el Archon
                $_SESSION['usuario_id'] = $archon['id'];
                $_SESSION['nombre'] = $archon['nombre'];
                $_SESSION['rol'] = 'archon';
                $_SESSION['empresa_nombre'] = ''; // Los Archons no están asociados a una empresa

                // Redirige al panel de control correspondiente
                redirigirPanel();
            } else {
                // Contraseña incorrecta
                $_SESSION['error'] = 'Contraseña incorrecta.';
            }
        } else {
            // Correo electrónico no encontrado en ninguna tabla
            $_SESSION['error'] = 'Correo electrónico no encontrado.';
        }
    }

    // Redirige de vuelta al formulario de inicio de sesión en caso de error
    header('Location: ../index.php');
    exit();
}