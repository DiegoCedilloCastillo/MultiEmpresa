<?php
// Iniciar la sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está autenticado
// Redirige al formulario de inicio de sesión si no hay usuario autenticado
function verificarAutenticacion() {
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: /multiempresa/auth/login.php');
        exit();
    }
}

// Verificar si la sesión está iniciada y si el rol del usuario es permitido
// Redirige al formulario de inicio de sesión o al panel de control según corresponda
function verificarSesionIniciada($rolesPermitidos = []) {
    if (!isset($_SESSION['usuario_id'])) {
        echo "No hay sesión iniciada. Redirigiendo a login<br>";
        header('Location: /multiempresa/auth/login.php');
        exit();
    }
    if (!empty($rolesPermitidos) && !in_array($_SESSION['rol'], $rolesPermitidos)) {
        echo "Rol no permitido. Redirigiendo al panel de control<br>";
        header('Location: /multiempresa/panel/panel_control.php');
        exit();
    }
}

// Verificar si el rol del usuario coincide con el rol requerido
// Redirige al panel de control si el rol no coincide
function verificarRol($rol) {
    if ($_SESSION['rol'] !== $rol) {
        header('Location: /multiempresa/panel/panel_control.php');
        exit();
    }
}

// Generar un ID único para una entidad específica
// Inserta un nuevo registro en la tabla 'ids' y retorna el ID generado
function generar_id($conn, $entidad) {
    $sql_ids = "INSERT INTO ids (entidad) VALUES (?)";
    $stmt = $conn->prepare($sql_ids);
    $stmt->bind_param("s", $entidad);
    $stmt->execute();
    $last_id = $stmt->insert_id;
    $stmt->close();
    return $last_id;
}

// Obtener el nombre de un usuario por su ID
// Retorna el nombre del usuario
function obtenerNombreUsuario($conn, $usuario_id) {
    $nombre = "";
    $sql_usuario = "SELECT nombre FROM usuarios WHERE id = ?";
    $stmt_usuario = $conn->prepare($sql_usuario);
    $stmt_usuario->bind_param("i", $usuario_id);
    $stmt_usuario->execute();
    $stmt_usuario->bind_result($nombre);
    $stmt_usuario->fetch();
    $stmt_usuario->close();
    return $nombre;
}

// Obtener el nombre de un Archon por su ID
// Retorna el nombre del Archon
function obtenerNombreArchon($conn, $archon_id) {
    $nombre = "";
    $sql_archon = "SELECT nombre FROM archons WHERE id = ?";
    $stmt_archon = $conn->prepare($sql_archon);
    $stmt_archon->bind_param("i", $archon_id);
    $stmt_archon->execute();
    $stmt_archon->bind_result($nombre);
    $stmt_archon->fetch();
    $stmt_archon->close();
    return $nombre;
}

// Redirigir al panel adecuado basado en el rol del usuario
// Redirige al panel correspondiente según el rol del usuario
function redirigirPanel() {
    switch ($_SESSION['rol']) {
        case 'archon':
            header('Location: /multiempresa/panel/panel_archon.php');
            break;
        case 'administrador':
        case 'administrador_base':
            header('Location: /multiempresa/panel/panel_admin.php');
            break;
        case 'gerente':
            header('Location: /multiempresa/panel/panel_gerente.php');
            break;
        case 'usuario':
            header('Location: /multiempresa/panel/panel_usuario.php');
            break;
        default:
            header('Location: /multiempresa/auth/login.php');
            break;
    }
    exit();
}

// Verificar acceso a un proyecto basado en el rol del usuario y la empresa
// Retorna true si el usuario tiene acceso, de lo contrario redirige
function verificarAccesoProyecto($conn, $id_proyecto) {
    if ($_SESSION['rol'] === 'archon') {
        return true;
    }

    $id_usuario = $_SESSION['usuario_id'];
    $id_empresa = $_SESSION['id_empresa'];

    $sql = "SELECT 1 FROM Proyectos WHERE id = ? AND id_empresa = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id_proyecto, $id_empresa);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        return true;
    } else {
        header('Location: ../proyectos/proyectos.php');
        exit();
    }
}

// Verificar acceso de edición a un proyecto basado en el rol del usuario
// Redirige si el rol no es permitido
function verificarAccesoEdicionProyecto($rol) {
    if (!in_array($rol, ['administrador', 'administrador_base'])) {
        header('Location: ../proyectos/proyectos.php');
        exit();
    }
}