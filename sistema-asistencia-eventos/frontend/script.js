// Variables globales
let eventos = [];
let asistentes = [];
let estadisticas = {};

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar AOS (Animate On Scroll)
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 800,
            easing: 'ease-out-cubic',
            once: true,
            offset: 50
        });
    }
    
    // Ocultar preloader
    setTimeout(() => {
        document.querySelector('.preloader').classList.add('fade-out');
        setTimeout(() => {
            document.querySelector('.preloader').style.display = 'none';
        }, 500);
    }, 1000);
    
    // Cargar datos iniciales
    cargarDashboard();
    cargarEventos();
    cargarAsistentes();
    
    // Configurar navegación
    configurarNavegacion();
    
    // Configurar eventos de formularios
    configurarFormularios();
    
    // Configurar modales
    configurarModales();
    
    // Inicializar gráficos
    inicializarGraficos();
    
    // Monitorear conexión
    monitorearConexion();
    
    // Configurar seguridad de formularios
    configurarSeguridadFormularios();
});

// Configurar navegación
function configurarNavegacion() {
    // Navegación por secciones
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const sectionId = this.getAttribute('data-section');
            mostrarSeccion(sectionId);
            
            // Actualizar navegación activa
            document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
            this.classList.add('active');
        });
    });
    
    // Logout
    document.querySelector('.logout-btn').addEventListener('click', function() {
        mostrarToast('Cerrando sesión...', 'warning');
        setTimeout(() => {
            window.location.href = 'index.html';
        }, 1500);
    });
}

// Mostrar sección con animación
function mostrarSeccion(seccionId) {
    // Ocultar todas las secciones con animación
    document.querySelectorAll('main > section').forEach(seccion => {
        if (seccion.classList.contains('dashboard-section')) {
            seccion.style.display = 'none';
        }
        seccion.classList.remove('section-activa');
        seccion.classList.add('section-oculta');
    });
    
    // Mostrar sección seleccionada con animación
    const seccion = document.getElementById(seccionId);
    if (seccion) {
        setTimeout(() => {
            seccion.classList.remove('section-oculta');
            seccion.classList.add('section-activa');
            
            if (seccionId === 'registro') {
                cargarEventos();
                document.getElementById('codigo_asistente').focus();
            } else if (seccionId === 'dashboard') {
                document.querySelector('.dashboard-section').style.display = 'block';
                actualizarDashboard();
            } else if (seccionId === 'reportes') {
                actualizarGraficos();
            }
            
            // Scroll suave al inicio
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }, 100);
    }
}

// Configurar formularios
function configurarFormularios() {
    // Formulario de registro de asistencia
    document.getElementById('formRegistroAsistencia').addEventListener('submit', function(e) {
        e.preventDefault();
        if (validarFormulario(this)) {
            registrarAsistencia();
        }
    });
    
    // Formulario de registro de asistente
    document.getElementById('formRegistrarAsistente').addEventListener('submit', function(e) {
        e.preventDefault();
        if (validarFormulario(this)) {
            registrarAsistente();
        }
    });
    
    // Formulario de registro de evento
    document.getElementById('formRegistrarEvento').addEventListener('submit', function(e) {
        e.preventDefault();
        if (validarFormulario(this)) {
            registrarEvento();
        }
    });
}

// Validación mejorada de formularios
function validarFormulario(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    
    inputs.forEach(input => {
        input.classList.remove('error');
        if (!input.value.trim()) {
            input.classList.add('error');
            isValid = false;
        }
        
        // Validaciones específicas
        if (input.type === 'email' && input.value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(input.value)) {
                input.classList.add('error');
                mostrarToast('Email inválido', 'error');
                isValid = false;
            }
        }
        
        if (input.type === 'tel' && input.value) {
            const phoneRegex = /^[\d\s\-\+\(\)]+$/;
            if (!phoneRegex.test(input.value)) {
                input.classList.add('error');
                mostrarToast('Teléfono inválido', 'error');
                isValid = false;
            }
        }
    });
    
    return isValid;
}

// Configurar modales
function configurarModales() {
    const modales = document.querySelectorAll('.modal');
    
    modales.forEach(modal => {
        // Botones cerrar
        modal.querySelectorAll('.modal-close').forEach(btn => {
            btn.addEventListener('click', () => cerrarModal(modal.id));
        });
        
        // Cerrar haciendo clic fuera
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModal(modal.id);
            }
        });
    });
    
    // Tecla Escape para cerrar modales
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            modales.forEach(modal => {
                if (modal.classList.contains('show')) {
                    cerrarModal(modal.id);
                }
            });
        }
    });
}

function mostrarModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

function cerrarModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
        
        // Resetear formulario
        const form = modal.querySelector('form');
        if (form) form.reset();
    }
}

function mostrarModalAsistente() {
    mostrarModal('modalAsistente');
}

function mostrarModalEvento() {
    mostrarModal('modalEvento');
}

// Cargar datos del dashboard
function cargarDashboard() {
    fetch('../backend/dashboard.php?accion=estadisticas', {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(handleResponse)
    .then(data => {
        if (data.success) {
            estadisticas = data.data;
            actualizarDashboard();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarToast('Error al cargar dashboard', 'error');
    });
}

function actualizarDashboard() {
    // Actualizar contadores
    if (estadisticas.totalEventos !== undefined) {
        animarContador('total-eventos', estadisticas.totalEventos);
    }
    if (estadisticas.totalAsistentes !== undefined) {
        animarContador('total-asistentes', estadisticas.totalAsistentes);
    }
    if (estadisticas.asistenciasHoy !== undefined) {
        animarContador('total-asistencias', estadisticas.asistenciasHoy);
    }
    
    // Cargar eventos próximos
    if (estadisticas.proximosEventos) {
        cargarEventosProximos(estadisticas.proximosEventos);
    }
    
    // Cargar actividad reciente
    if (estadisticas.actividadReciente) {
        cargarActividadReciente(estadisticas.actividadReciente);
    }
}

function animarContador(elementId, valorFinal) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    let valorInicial = parseInt(element.textContent) || 0;
    let incremento = valorFinal > valorInicial ? 1 : -1;
    let velocidad = Math.abs(valorFinal - valorInicial) > 100 ? 1 : 10;
    
    function actualizar() {
        valorInicial += incremento;
        element.textContent = valorInicial;
        
        if ((incremento > 0 && valorInicial < valorFinal) || 
            (incremento < 0 && valorInicial > valorFinal)) {
            setTimeout(actualizar, velocidad);
        } else {
            element.textContent = valorFinal;
        }
    }
    
    actualizar();
}

function cargarEventosProximos(eventos) {
    const container = document.getElementById('eventos-proximos');
    if (!container) return;
    
    container.innerHTML = '';
    
    eventos.forEach(evento => {
        const fecha = new Date(evento.fecha);
        const eventoHTML = `
            <div class="event-item" data-aos="fade-up">
                <div class="event-date">
                    <div class="day">${fecha.getDate()}</div>
                    <div class="month">${fecha.toLocaleString('es', { month: 'short' })}</div>
                </div>
                <div class="event-info">
                    <h4>${sanitizarHTML(evento.nombre)}</h4>
                    <p>${sanitizarHTML(evento.ubicacion)}</p>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', eventoHTML);
    });
}

function cargarActividadReciente(actividades) {
    const container = document.getElementById('actividad-reciente');
    if (!container) return;
    
    container.innerHTML = '';
    
    actividades.forEach(actividad => {
        const actividadHTML = `
            <div class="activity-item" data-aos="fade-up">
                <div class="activity-icon">
                    <i class="fas ${actividad.icono || 'fa-history'}"></i>
                </div>
                <div class="activity-info">
                    <p>${sanitizarHTML(actividad.descripcion)}</p>
                    <span class="activity-time">${actividad.tiempo}</span>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', actividadHTML);
    });
}

// Funciones de registro (seguras)
function registrarAsistencia() {
    const form = document.getElementById('formRegistroAsistencia');
    const formData = new FormData(form);
    
    // Agregar token CSRF
    formData.append('csrf_token', obtenerTokenCSRF());
    
    // Agregar timestamp
    formData.append('timestamp', Date.now());
    
    // Mostrar loading
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando...';
    submitBtn.disabled = true;
    
    fetch('../backend/registrar_asistencias.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(handleResponse)
    .then(data => {
        if (data.success) {
            mostrarToast(data.message, 'success');
            form.reset();
            
            // Mostrar resultado detallado
            mostrarResultadoVerificacion(data.data);
            
            // Actualizar dashboard
            cargarDashboard();
        } else {
            mostrarToast(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarToast('Error de conexión con el servidor', 'error');
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

function registrarAsistente() {
    const form = document.getElementById('formRegistrarAsistente');
    const formData = new FormData(form);
    
    // Agregar token CSRF
    formData.append('csrf_token', obtenerTokenCSRF());
    
    fetch('../backend/registrar_asistentes.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(handleResponse)
    .then(data => {
        if (data.success) {
            mostrarToast(data.message, 'success');
            cerrarModal('modalAsistente');
            cargarAsistentes();
            cargarDashboard();
        } else {
            mostrarToast(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarToast('Error de conexión con el servidor', 'error');
    });
}

function registrarEvento() {
    const form = document.getElementById('formRegistrarEvento');
    const formData = new FormData(form);
    
    // Agregar token CSRF
    formData.append('csrf_token', obtenerTokenCSRF());
    
    fetch('../backend/registrar_eventos.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(handleResponse)
    .then(data => {
        if (data.success) {
            mostrarToast(data.message, 'success');
            cerrarModal('modalEvento');
            cargarEventos();
            cargarDashboard();
        } else {
            mostrarToast(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarToast('Error de conexión con el servidor', 'error');
    });
}

// Manejo de respuesta mejorado
function handleResponse(response) {
    // Verificar si la respuesta es JSON
    const contentType = response.headers.get('content-type');
    if (!contentType || !contentType.includes('application/json')) {
        throw new TypeError('Respuesta no es JSON');
    }
    
    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    return response.json();
}

// Mostrar resultado de verificación
function mostrarResultadoVerificacion(datos) {
    const container = document.getElementById('resultado-verificacion');
    if (!container) return;
    
    const resultadoHTML = `
        <div class="card verification-success" data-aos="zoom-in">
            <div class="verification-header">
                <i class="fas fa-check-circle"></i>
                <h3>Verificación Exitosa</h3>
            </div>
            <div class="verification-details">
                <div class="detail-item">
                    <span class="detail-label">Asistente:</span>
                    <span class="detail-value">${sanitizarHTML(datos.nombre)}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Documento:</span>
                    <span class="detail-value">${sanitizarHTML(datos.documento)}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Evento:</span>
                    <span class="detail-value">${sanitizarHTML(datos.evento)}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Hora:</span>
                    <span class="detail-value">${new Date(datos.fecha_hora).toLocaleTimeString()}</span>
                </div>
            </div>
            <div class="verification-code">
                <span>Código de verificación:</span>
                <strong>${sanitizarHTML(datos.codigo_verificacion)}</strong>
            </div>
        </div>
    `;
    
    container.innerHTML = resultadoHTML;
    
    // Auto-ocultar después de 10 segundos
    setTimeout(() => {
        container.innerHTML = '';
    }, 10000);
}

// Cargar eventos para select
function cargarEventos() {
    fetch('../backend/registrar_eventos.php?accion=listar', {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(handleResponse)
    .then(data => {
        if (data.success) {
            eventos = data.eventos;
            const selectEventos = document.getElementById('evento_id');
            if (selectEventos) {
                selectEventos.innerHTML = '<option value="">Seleccione un evento</option>';
                eventos.forEach(evento => {
                    const option = document.createElement('option');
                    option.value = evento.id;
                    option.textContent = `${sanitizarHTML(evento.nombre)} (${evento.fecha})`;
                    selectEventos.appendChild(option);
                });
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// Cargar asistentes para tabla
function cargarAsistentes() {
    fetch('../backend/registrar_asistentes.php?accion=listar', {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(handleResponse)
    .then(data => {
        if (data.success) {
            asistentes = data.asistentes;
            const tbody = document.querySelector('#tabla-asistentes tbody');
            if (tbody) {
                tbody.innerHTML = '';
                asistentes.forEach(asistente => {
                    const fila = document.createElement('tr');
                    fila.innerHTML = `
                        <td><span class="badge-code">${sanitizarHTML(asistente.codigo)}</span></td>
                        <td>${sanitizarHTML(asistente.nombre)}</td>
                        <td>${sanitizarHTML(asistente.documento)}</td>
                        <td>${sanitizarHTML(asistente.email)}</td>
                        <td>${sanitizarHTML(asistente.telefono)}</td>
                        <td>${new Date(asistente.fecha_registro).toLocaleDateString()}</td>
                        <td>
                            <button class="btn-action" onclick="editarAsistente(${asistente.id})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-action btn-danger" onclick="eliminarAsistente(${asistente.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    `;
                    tbody.appendChild(fila);
                });
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// Inicializar gráficos
function inicializarGraficos() {
    // Gráfico de asistencias por evento
    const ctx1 = document.getElementById('chartAsistencias');
    if (ctx1) {
        window.chartAsistencias = new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Asistencias',
                    data: [],
                    backgroundColor: 'rgba(67, 97, 238, 0.7)',
                    borderColor: 'rgba(67, 97, 238, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    // Gráfico de tendencia mensual
    const ctx2 = document.getElementById('chartTendencia');
    if (ctx2) {
        window.chartTendencia = new Chart(ctx2, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Asistencias',
                    data: [],
                    backgroundColor: 'rgba(76, 201, 240, 0.1)',
                    borderColor: 'rgba(76, 201, 240, 1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
}

// Actualizar gráficos
function actualizarGraficos() {
    fetch('../backend/reportes.php?accion=graficos', {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(handleResponse)
    .then(data => {
        if (data.success) {
            if (window.chartAsistencias && data.asistenciasPorEvento) {
                window.chartAsistencias.data.labels = data.asistenciasPorEvento.labels;
                window.chartAsistencias.data.datasets[0].data = data.asistenciasPorEvento.data;
                window.chartAsistencias.update();
            }
            
            if (window.chartTendencia && data.tendenciaMensual) {
                window.chartTendencia.data.labels = data.tendenciaMensual.labels;
                window.chartTendencia.data.datasets[0].data = data.tendenciaMensual.data;
                window.chartTendencia.update();
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// Monitorear conexión
function monitorearConexion() {
    const serverStatus = document.getElementById('server-status');
    const dbStatus = document.getElementById('db-status');
    const lastUpdate = document.getElementById('last-update');
    
    function actualizarEstado() {
        fetch('../backend/health_check.php', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (response.ok) {
                if (serverStatus) {
                    serverStatus.textContent = 'En línea';
                    serverStatus.style.color = '#4cc9f0';
                }
                return response.json();
            }
            throw new Error('Server error');
        })
        .then(data => {
            if (data.db_connected && dbStatus) {
                dbStatus.textContent = 'Conectada';
                dbStatus.style.color = '#4cc9f0';
            }
            if (lastUpdate) {
                lastUpdate.textContent = new Date().toLocaleTimeString();
            }
        })
        .catch(error => {
            if (serverStatus) {
                serverStatus.textContent = 'Fuera de línea';
                serverStatus.style.color = '#f72585';
            }
            if (dbStatus) {
                dbStatus.textContent = 'Desconectada';
                dbStatus.style.color = '#f72585';
            }
        });
    }
    
    // Actualizar cada 30 segundos
    actualizarEstado();
    setInterval(actualizarEstado, 30000);
}

// Configurar seguridad de formularios
function configurarSeguridadFormularios() {
    // Prevenir XSS en inputs
    document.addEventListener('input', function(e) {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
            const originalValue = e.target.value;
            const sanitizedValue = sanitizarHTML(originalValue);
            if (originalValue !== sanitizedValue) {
                e.target.value = sanitizedValue;
            }
        }
    });
}

// Funciones de seguridad
function obtenerTokenCSRF() {
    let token = localStorage.getItem('csrf_token');
    if (!token) {
        token = generarTokenCSRF();
        localStorage.setItem('csrf_token', token);
    }
    return token;
}

function generarTokenCSRF() {
    const array = new Uint8Array(32);
    window.crypto.getRandomValues(array);
    return Array.from(array, byte => byte.toString(16).padStart(2, '0')).join('');
}

function sanitizarHTML(texto) {
    const div = document.createElement('div');
    div.textContent = texto;
    return div.innerHTML;
}

// Funciones de utilidad
function mostrarToast(mensaje, tipo = 'info') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast toast-${tipo}`;
    toast.innerHTML = `
        <i class="fas ${tipo === 'success' ? 'fa-check-circle' : tipo === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
        <span>${sanitizarHTML(mensaje)}</span>
    `;
    
    container.appendChild(toast);
    
    // Auto-remover después de 5 segundos
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => {
            container.removeChild(toast);
        }, 300);
    }, 5000);
}

function exportarAsistentes() {
    mostrarToast('Generando archivo de exportación...', 'info');
    
    fetch('../backend/exportar.php?tipo=asistentes', {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.blob())
    .then(blob => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `asistentes_${new Date().toISOString().split('T')[0]}.csv`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
        
        mostrarToast('Exportación completada', 'success');
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarToast('Error al exportar', 'error');
    });
}

function generarReporte() {
    mostrarToast('Generando reporte completo...', 'info');
    
    fetch('../backend/reportes.php?accion=generar', {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarToast('Reporte generado exitosamente', 'success');
            // Aquí podrías mostrar el reporte en un modal o nueva pestaña
        } else {
            mostrarToast(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarToast('Error al generar reporte', 'error');
    });
}

// Funciones adicionales para edición/eliminación
function editarAsistente(id) {
    const asistente = asistentes.find(a => a.id == id);
    if (asistente) {
        mostrarToast(`Editando asistente: ${asistente.nombre}`, 'info');
        // Aquí implementarías la lógica de edición
    }
}

function eliminarAsistente(id) {
    if (confirm('¿Está seguro de eliminar este asistente?')) {
        fetch('../backend/registrar_asistentes.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                id: id,
                csrf_token: obtenerTokenCSRF()
            })
        })
        .then(handleResponse)
        .then(data => {
            if (data.success) {
                mostrarToast('Asistente eliminado', 'success');
                cargarAsistentes();
                cargarDashboard();
            } else {
                mostrarToast(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarToast('Error al eliminar asistente', 'error');
        });
    }
}

// Atajos de teclado
document.addEventListener('keydown', function(e) {
    // Ctrl + S para guardar (en formularios)
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        const activeForm = document.querySelector('form:not(.section-oculta)');
        if (activeForm) {
            activeForm.dispatchEvent(new Event('submit'));
        }
    }
    
    // Escape para cancelar
    if (e.key === 'Escape') {
        const modales = document.querySelectorAll('.modal.show');
        if (modales.length > 0) {
            cerrarModal(modales[0].id);
        }
    }
});
