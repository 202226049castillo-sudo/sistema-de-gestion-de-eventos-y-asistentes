// Variables globales
let eventos = [];

// Cargar eventos al iniciar
document.addEventListener('DOMContentLoaded', function() {
    cargarEventos();
    
    // Asignar eventos a los formularios
    document.getElementById('formRegistroAsistencia').addEventListener('submit', registrarAsistencia);
    document.getElementById('formRegistrarAsistente').addEventListener('submit', registrarAsistente);
    document.getElementById('formRegistrarEvento').addEventListener('submit', registrarEvento);
});

// Función para mostrar secciones
function mostrarSeccion(seccionId) {
    // Ocultar todas las secciones
    document.querySelectorAll('main section').forEach(seccion => {
        seccion.classList.remove('seccion-activa');
        seccion.classList.add('seccion-oculta');
    });
    
    // Mostrar la sección seleccionada
    const seccion = document.getElementById(seccionId);
    if (seccion) {
        seccion.classList.remove('seccion-oculta');
        seccion.classList.add('seccion-activa');
    }
    
    // Si es la sección de registro, recargar eventos
    if (seccionId === 'registro') {
        cargarEventos();
    }
}

// Función para cargar eventos desde el servidor
function cargarEventos() {
    fetch('../backend/registrar_eventos.php?accion=listar')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                eventos = data.eventos;
                const selectEventos = document.getElementById('evento_id');
                selectEventos.innerHTML = '<option value="">Seleccione un evento</option>';
                
                eventos.forEach(evento => {
                    const option = document.createElement('option');
                    option.value = evento.id;
                    option.textContent = `${evento.nombre} (${evento.fecha})`;
                    selectEventos.appendChild(option);
                });
            } else {
                mostrarMensaje('Error al cargar eventos: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarMensaje('Error de conexión con el servidor', 'error');
        });
}

// Función para registrar asistencia
function registrarAsistencia(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    
    fetch('../backend/registrar_asistencias.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarMensaje(data.message, 'exito');
            event.target.reset();
        } else {
            mostrarMensaje(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarMensaje('Error de conexión con el servidor', 'error');
    });
}

// Función para registrar asistente
function registrarAsistente(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    
    fetch('../backend/registrar_asistentes.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarMensaje(data.message, 'exito');
            event.target.reset();
        } else {
            mostrarMensaje(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarMensaje('Error de conexión con el servidor', 'error');
    });
}

// Función para registrar evento
function registrarEvento(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    
    fetch('../backend/registrar_eventos.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarMensaje(data.message, 'exito');
            event.target.reset();
            cargarEventos(); // Recargar la lista de eventos
        } else {
            mostrarMensaje(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarMensaje('Error de conexión con el servidor', 'error');
    });
}

// Función para mostrar mensajes
function mostrarMensaje(mensaje, tipo) {
    const mensajeDiv = document.getElementById('mensaje');
    mensajeDiv.textContent = mensaje;
    mensajeDiv.className = ''; // Limpiar clases previas
    mensajeDiv.classList.add(`mensaje-${tipo}`);
    
    // Ocultar mensaje después de 5 segundos
    setTimeout(() => {
        mensajeDiv.textContent = '';
        mensajeDiv.className = '';
    }, 5000);
}