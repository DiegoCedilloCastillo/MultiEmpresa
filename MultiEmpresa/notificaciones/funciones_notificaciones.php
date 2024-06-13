<?php
// Función para crear una nueva notificación para un usuario específico
function crearNotificacion($conn, $mensaje, $usuario_id, $id_empresa = null, $excluir_usuario = null) {
    // Verificar que el usuario exista antes de crear una notificación
    $sql_check_user = "SELECT id FROM usuarios WHERE id = ?";
    $stmt_check_user = $conn->prepare($sql_check_user);
    $stmt_check_user->bind_param("i", $usuario_id);
    $stmt_check_user->execute();
    $result_check_user = $stmt_check_user->get_result();

    // Si el usuario existe, procede a crear la notificación
    if ($result_check_user->num_rows > 0) {
        // Crear una entrada en la tabla IDs para la notificación
        $sql_ids = "INSERT INTO ids (entidad) VALUES ('notificacion')";
        if ($conn->query($sql_ids) === TRUE) {
            $last_id = $conn->insert_id;
            error_log("ID general creado: $last_id");

            // Crear la notificación en la tabla de notificaciones
            $sql = "INSERT INTO notificaciones (id_general, mensaje, fecha, id_usuario, leida) 
                    VALUES (?, ?, NOW(), ?, FALSE)";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                error_log("Error preparing statement: " . $conn->error);
                return;
            }
            $stmt->bind_param("isi", $last_id, $mensaje, $usuario_id);
            if ($stmt->execute() === FALSE) {
                error_log("Error executing statement: " . $stmt->error);
            } else {
                error_log("Notificación creada: ID general $last_id para usuario $usuario_id");
            }
            $stmt->close();

            // Si se especifica una empresa, notificar a otros usuarios de la misma empresa
            if ($id_empresa !== null) {
                $sql_usuarios = "SELECT id FROM usuarios WHERE id_empresa = ?";
                if ($excluir_usuario !== null) {
                    $sql_usuarios .= " AND id != ?";
                }
                $stmt_usuarios = $conn->prepare($sql_usuarios);
                if ($excluir_usuario !== null) {
                    $stmt_usuarios->bind_param("ii", $id_empresa, $excluir_usuario);
                } else {
                    $stmt_usuarios->bind_param("i", $id_empresa);
                }
                $stmt_usuarios->execute();
                $result_usuarios = $stmt_usuarios->get_result();

                // Crear notificaciones para cada usuario de la empresa
                while ($row = $result_usuarios->fetch_assoc()) {
                    crearNotificacionUsuario($conn, $row['id'], $mensaje);
                }
                $stmt_usuarios->close();
            }
        } else {
            error_log("Error al insertar en la tabla ids: " . $conn->error);
        }
    } else {
        error_log("Usuario no encontrado al crear notificación: ID $usuario_id");
    }
}

// Función para crear notificaciones de actualización para todos los usuarios de la empresa del usuario excepto uno específico
function crearNotificacionActualizacion($conn, $mensaje, $usuario_id) {
    // Obtener el id_empresa del usuario
    $sql_usuario = "SELECT id_empresa FROM usuarios WHERE id = ?";
    $stmt_usuario = $conn->prepare($sql_usuario);
    $stmt_usuario->bind_param("i", $usuario_id);
    $stmt_usuario->execute();
    $result_usuario = $stmt_usuario->get_result();
    $usuario = $result_usuario->fetch_assoc();
    $id_empresa = $usuario['id_empresa'];

    // Obtener todos los usuarios de la empresa excepto el usuario especificado
    $sql_usuarios = "SELECT id FROM usuarios WHERE id_empresa = ? AND id != ?";
    $stmt_usuarios = $conn->prepare($sql_usuarios);
    $stmt_usuarios->bind_param("ii", $id_empresa, $usuario_id);
    $stmt_usuarios->execute();
    $result_usuarios = $stmt_usuarios->get_result();

    // Crear notificaciones para cada usuario
    while ($row = $result_usuarios->fetch_assoc()) {
        $id_usuario = $row['id'];
        
        $sql_ids = "INSERT INTO ids (entidad) VALUES ('notificacion')";
        if ($conn->query($sql_ids) === TRUE) {
            $last_id = $conn->insert_id;
            error_log("ID general creado: $last_id");

            $sql = "INSERT INTO notificaciones (id_general, mensaje, fecha, id_usuario, leida) 
                    VALUES (?, ?, NOW(), ?, FALSE)";
            $stmt_notif = $conn->prepare($sql);
            if ($stmt_notif === false) {
                error_log("Error preparing statement: " . $conn->error);
                return;
            }
            $stmt_notif->bind_param("isi", $last_id, $mensaje, $id_usuario);
            if ($stmt_notif->execute() === FALSE) {
                error_log("Error executing statement: " . $stmt_notif->error);
            } else {
                error_log("Notificación creada: ID general $last_id para usuario $id_usuario");
            }
            $stmt_notif->close();
        } else {
            error_log("Error al insertar en la tabla ids: " . $conn->error);
        }
    }
}

// Función para crear notificaciones para todos los usuarios de una empresa específica
function crearNotificacionEmpresa($conn, $id_empresa, $mensaje, $excluir_usuario = null) {
    // Obtener todos los usuarios de la empresa especificada
    $sql_usuarios = "SELECT id FROM usuarios WHERE id_empresa = ?";
    if ($excluir_usuario !== null) {
        $sql_usuarios .= " AND id != ?";
    }

    $stmt = $conn->prepare($sql_usuarios);
    if ($stmt === false) {
        error_log("Error preparing statement: " . $conn->error);
        return;
    }
    if ($excluir_usuario !== null) {
        $stmt->bind_param("ii", $id_empresa, $excluir_usuario);
    } else {
        $stmt->bind_param("i", $id_empresa);
    }
    $stmt->execute();
    $result_usuarios = $stmt->get_result();

    // Crear notificaciones para cada usuario de la empresa
    while ($row = $result_usuarios->fetch_assoc()) {
        crearNotificacionUsuario($conn, $row['id'], $mensaje);
    }
}

// Función para crear una notificación para un usuario específico
function crearNotificacionUsuario($conn, $id_usuario, $mensaje) {
    // Verificar que el usuario exista antes de crear una notificación
    $sql_check_user = "SELECT id FROM usuarios WHERE id = ?";
    $stmt_check_user = $conn->prepare($sql_check_user);
    if ($stmt_check_user === false) {
        error_log("Error preparing statement: " . $conn->error);
        return;
    }
    $stmt_check_user->bind_param("i", $id_usuario);
    $stmt_check_user->execute();
    $result_check_user = $stmt_check_user->get_result();

    // Si el usuario existe, procede a crear la notificación
    if ($result_check_user->num_rows > 0) {
        // Crear una entrada en la tabla IDs para la notificación
        $sql_ids = "INSERT INTO ids (entidad) VALUES ('notificacion')";
        if ($conn->query($sql_ids) === TRUE) {
            $last_id = $conn->insert_id;

            // Crear la notificación en la tabla de notificaciones
            $sql = "INSERT INTO notificaciones (id_general, mensaje, fecha, id_usuario, leida) 
                    VALUES (?, ?, NOW(), ?, FALSE)";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                error_log("Error preparing statement: " . $conn->error);
                return;
            }
            $stmt->bind_param("isi", $last_id, $mensaje, $id_usuario);
            if ($stmt->execute() === FALSE) {
                error_log("Error executing statement: " . $stmt->error);
            } else {
                error_log("Notificación creada: ID general $last_id para usuario $id_usuario");
            }
            $stmt->close();
        } else {
            error_log("Error al insertar en la tabla ids: " . $conn->error);
        }
    } else {
        error_log("Usuario no encontrado al crear notificación: ID $id_usuario");
    }
}

// Función para crear notificaciones para administradores y gerentes de una empresa específica
function crearNotificacionAdminsYGerentes($conn, $id_empresa, $accion, $id_usuario) {
    // Obtener todos los administradores y gerentes de la empresa especificada, excluyendo al usuario dado
    $sql_usuarios = "SELECT id FROM usuarios WHERE id_empresa = ? AND rol IN ('administrador', 'gerente') AND id != ?";
    $stmt = $conn->prepare($sql_usuarios);
    if ($stmt === false) {
        error_log("Error preparing statement: " . $conn->error);
        return;
    }
    $stmt->bind_param("ii", $id_empresa, $id_usuario);
    $stmt->execute();
    $result_usuarios = $stmt->get_result();

    // Crear notificaciones para cada administrador y gerente
    while ($row = $result_usuarios->fetch_assoc()) {
        crearNotificacionUsuario($conn, $row['id'], $accion);
    }
}

// Función para crear notificaciones para Archons y administradores base de la empresa
function crearNotificacionArchons($conn, $mensaje, $usuario_id, $id_empresa = null) {
    // Obtener todos los Archons excepto el usuario dado
    $sql_archons = "SELECT id FROM archons WHERE id != ?";
    $stmt = $conn->prepare($sql_archons);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result_archons = $stmt->get_result();

    // Crear notificaciones para cada Archon
    while ($row = $result_archons->fetch_assoc()) {
        $id_archon = $row['id'];
        crearNotificacionUsuario($conn, $id_archon, $mensaje);
    }

    // Si se especifica una empresa, notificar al administrador base
    if ($id_empresa !== null) {
        $sql_admin_base = "SELECT id FROM usuarios WHERE rol = 'administrador_base' AND id_empresa = ?";
        $stmt_admin_base = $conn->prepare($sql_admin_base);
        $stmt_admin_base->bind_param("i", $id_empresa);
        $stmt_admin_base->execute();
        $result_admin_base = $stmt_admin_base->get_result();

        // Crear notificación para el administrador base
        if ($result_admin_base->num_rows > 0) {
            $admin_base = $result_admin_base->fetch_assoc();
            crearNotificacionUsuario($conn, $admin_base['id'], $mensaje);
        }
    }
}