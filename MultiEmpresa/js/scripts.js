// Validar Formulario de Registro
// Comprueba que todos los campos estén llenos y que las contraseñas coincidan
function validarFormularioRegistro() {
    var nombre = document.getElementById('nombre').value;
    var apellido = document.getElementById('apellido').value;
    var email = document.getElementById('email').value;
    var password = document.getElementById('password').value;
    var confirm_password = document.getElementById('confirm_password').value;

    if (nombre === '' || apellido === '' || email === '' || password === '' || confirm_password === '') {
        alert('Todos los campos son obligatorios.');
        return false;
    }

    if (password !== confirm_password) {
        alert('Las contraseñas no coinciden.');
        return false;
    }

    return true;
}

// Validar Formulario de Inicio de Sesión
// Comprueba que todos los campos estén llenos
function validarFormularioInicioSesion() {
    var email = document.getElementById('email').value;
    var password = document.getElementById('password').value;
    
    if (email === '' || password === '') {
        alert('Todos los campos son obligatorios.');
        return false;
    }
    return true;
}

// Actualizar Tareas Próximas a Vencer
// Lógica para actualizar tareas próximas a vencer en tiempo real
function actualizarTareasProximasAVencer() {
    // Implementación de la lógica para actualizar tareas
}

// Mostrar/Ocultar Contraseña
// Cambia el tipo de entrada entre 'password' y 'text' para mostrar u ocultar la contraseña
function mostrarOcultarContrasena(id) {
    var input = document.getElementById(id);
    if (input.type === 'password') {
        input.type = 'text';
    } else {
        input.type = 'password';
    }
}

// Interacción del Menú Desplegable
// Lógica para manejar interacción con menús desplegables
function manejarMenuDesplegable() {
    // Implementación de la lógica para menús desplegables
}

// Notificaciones en Tiempo Real
// Lógica para mostrar notificaciones en tiempo real
function mostrarNotificacionesEnTiempoReal() {
    // Implementación de la lógica para notificaciones en tiempo real
}

// Confirmación de Eliminación
// Muestra una alerta de confirmación antes de proceder con la eliminación
function confirmarEliminacion() {
    return confirm('¿Estás seguro de que deseas eliminar esto?');
}

// Carga Dinámica de Datos
// Carga datos dinámicamente usando Fetch API y actualiza el contenido del elemento especificado
function cargarDatosDinamicamente(url, elemento) {
    fetch(url)
        .then(response => response.json())
        .then(data => {
            document.getElementById(elemento).innerHTML = data;
        })
        .catch(error => console.error('Error:', error));
}

// Filtrar Listas
// Lógica para filtrar listas según un criterio especificado
function filtrarListas(criterio) {
    // Implementación de la lógica para filtrar listas
}

// Manejo de Errores
// Muestra un mensaje de error en una alerta
function manejarErrores(mensaje) {
    alert(mensaje);
}

// Actualizar Perfil de Usuario
// Lógica para manejar la actualización del perfil del usuario
function actualizarPerfilUsuario() {
    // Implementación de la lógica para actualizar el perfil del usuario
}

// Desplegar/Contraer Secciones
// Muestra u oculta una sección según su estado actual
function desplegarContraerSeccion(id) {
    var seccion = document.getElementById(id);
    if (seccion.style.display === 'none') {
        seccion.style.display = 'block';
    } else {
        seccion.style.display = 'none';
    }
}

// Event Listeners para formularios
// Validar el formulario de registro antes de enviarlo
document.getElementById('formularioRegistro')?.addEventListener('submit', function (event) {
    if (!validarFormularioRegistro()) {
        event.preventDefault();
    }
});

// Validar el formulario de inicio de sesión antes de enviarlo
document.getElementById('formularioInicioSesion')?.addEventListener('submit', function (event) {
    if (!validarFormularioInicioSesion()) {
        event.preventDefault();
    }
});

// Lógica para actualización en tiempo real de tareas próximas a vencer
// Ejecuta la función cada minuto
setInterval(actualizarTareasProximasAVencer, 60000); // Actualiza cada minuto

// Lógica para mostrar notificaciones en tiempo real
// Ejecuta la función cada minuto
setInterval(mostrarNotificacionesEnTiempoReal, 60000); // Actualiza cada minuto