let eventos = [];
let asistentes = [];
let registros = [];

function crearEvento() {
    const nombre = document.getElementById("eventoNombre").value;
    if (nombre === "") {
        alert("Ingrese un nombre de evento");
        return;
    }

    eventos.push(nombre);
    actualizarEventos();
    document.getElementById("eventoNombre").value = "";
}

function crearAsistente() {
    const nombre = document.getElementById("asistenteNombre").value;
    if (nombre === "") {
        alert("Ingrese un nombre de asistente");
        return;
    }

    asistentes.push(nombre);
    actualizarAsistentes();
    document.getElementById("asistenteNombre").value = "";
}

function registrarAsistencia() {
    const evento = document.getElementById("selectEvento").value;
    const asistente = document.getElementById("selectAsistente").value;

    registros.push({ evento, asistente });
    alert("Asistencia registrada correctamente");
}

function verAsistencia() {
    const evento = document.getElementById("consultaEvento").value;
    const lista = document.getElementById("listaAsistencia");
    lista.innerHTML = "";

    registros
        .filter(r => r.evento === evento)
        .forEach(r => {
            const li = document.createElement("li");
            li.textContent = r.asistente;
            lista.appendChild(li);
        });
}

function actualizarEventos() {
    actualizarLista("listaEventos", eventos);
    actualizarSelect("selectEvento", eventos);
    actualizarSelect("consultaEvento", eventos);
}

function actualizarAsistentes() {
    actualizarLista("listaAsistentes", asistentes);
    actualizarSelect("selectAsistente", asistentes);
}

function actualizarLista(id, datos) {
    const ul = document.getElementById(id);
    ul.innerHTML = "";

    datos.forEach(dato => {
        const li = document.createElement("li");
        li.textContent = dato;
        ul.appendChild(li);
    });
}

function actualizarSelect(id, datos) {
    const select = document.getElementById(id);
    select.innerHTML = "";

    datos.forEach(dato => {
        const option = document.createElement("option");
        option.value = dato;
        option.textContent = dato;
        select.appendChild(option);
    });
}
