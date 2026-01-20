const API = "backend";

// ================================
// NAVEGACIÓN
// ================================
function mostrarSeccion(id) {
    document.querySelectorAll(".seccion, #menu").forEach(sec => {
        sec.style.display = "none";
    });
    document.getElementById(id).style.display = "block";
}

document.addEventListener("DOMContentLoaded", () => {
    mostrarSeccion("menu");
    cargarEventos();
    cargarAsistentes();
});

// ================================
// EVENTOS
// ================================
async function guardarEvento() {
    const nombre = eventoNombre.value;
    const fecha = eventoFecha.value;
    const lugar = eventoLugar.value;

    if (!nombre || !fecha || !lugar) {
        alert("Complete todos los campos del evento");
        return;
    }

    await fetch(`${API}/guardar_evento.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ nombre, fecha, lugar })
    });

    eventoNombre.value = "";
    eventoFecha.value = "";
    eventoLugar.value = "";

    cargarEventos();
}

async function cargarEventos() {
    const res = await fetch(`${API}/eventos.php`);
    const eventos = await res.json();

    listaEventos.innerHTML = "";
    selectEvento.innerHTML = "<option value=''>Seleccione evento</option>";
    consultaEvento.innerHTML = "<option value=''>Seleccione evento</option>";

    eventos.forEach(e => {
        listaEventos.innerHTML += `<li>${e.nombre} - ${e.fecha} - ${e.lugar}</li>`;
        selectEvento.innerHTML += `<option value="${e.id}">${e.nombre}</option>`;
        consultaEvento.innerHTML += `<option value="${e.id}">${e.nombre}</option>`;
    });
}

// ================================
// ASISTENTES
// ================================
async function guardarAsistente() {
    const nombre = asistenteNombre.value;

    if (!nombre) {
        alert("Ingrese el nombre del asistente");
        return;
    }

    await fetch(`${API}/guardar_asistente.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ nombre })
    });

    asistenteNombre.value = "";
    cargarAsistentes();
}

async function cargarAsistentes() {
    const res = await fetch(`${API}/asistentes.php`);
    const asistentes = await res.json();

    listaAsistentes.innerHTML = "";
    selectAsistente.innerHTML = "<option value=''>Seleccione asistente</option>";

    asistentes.forEach(a => {
        listaAsistentes.innerHTML += `<li>${a.nombre}</li>`;
        selectAsistente.innerHTML += `<option value="${a.id}">${a.nombre}</option>`;
    });
}

// ================================
// ASISTENCIA
// ================================
async function registrarAsistencia() {
    if (!selectEvento.value || !selectAsistente.value) {
        alert("Seleccione evento y asistente");
        return;
    }

    await fetch(`${API}/guardar_asistencia.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            eventoId: selectEvento.value,
            asistenteId: selectAsistente.value
        })
    });

    alert("Asistencia registrada correctamente");
}

// ================================
// CONSULTA
// ================================
async function verAsistencia() {
    if (!consultaEvento.value) return;

    const asistencias = await fetch(`${API}/asistencias.php`).then(r => r.json());
    const asistentes = await fetch(`${API}/asistentes.php`).then(r => r.json());

    listaAsistencia.innerHTML = "";

    asistencias
        .filter(a => a.eventoId === consultaEvento.value)
        .forEach(a => {
            const asistente = asistentes.find(x => x.id === a.asistenteId);
            if (asistente) {
                listaAsistencia.innerHTML += `<li>${asistente.nombre}</li>`;
            }
        });
}
