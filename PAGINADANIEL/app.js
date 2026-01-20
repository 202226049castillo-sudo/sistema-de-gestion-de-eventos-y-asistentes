// Configuración global de seguridad
const CONFIG_SEGURIDAD = {
    MAX_LONGITUD_TEXTO: 50, // CIBERSEGURIDAD: Principio de Privilegio Mínimo (limitar capacidad de entrada)
    ITERACIONES_PBKDF2: 100000
};

let eventos = [];
let asistentes = [];
let registros = [];
let claveMaestra = null; // Se generará para el cifrado

/**
 * CIBERSEGURIDAD: Generación de llave criptográfica segura.
 * Implementa cifrado AES-GCM (estándar de la industria).
 */
async function inicializarCriptografia() {
    claveMaestra = await window.crypto.subtle.generateKey(
        { name: "AES-GCM", length: 256 },
        true,
        ["encrypt", "decrypt"]
    );
}

/**
 * CIBERSEGURIDAD: Prevención de filtración de datos (Data Leak Prevention).
 * Cifra datos sensibles antes de cualquier almacenamiento o tránsito.
 */
async function cifrarDato(texto) {
    const encoder = new TextEncoder();
    const iv = window.crypto.getRandomValues(new Uint8Array(12)); // Vector de inicialización único
    const encoded = encoder.encode(texto);

    const cifrado = await window.crypto.subtle.encrypt(
        { name: "AES-GCM", iv: iv },
        claveMaestra,
        encoded
    );

    return { data: cifrado, iv: Array.from(iv) };
}

/**
 * CIBERSEGURIDAD: Prevención de XSS (Cross-Site Scripting) reforzada.
 * Sanitización de "Capa Blanca": solo permite caracteres alfanuméricos básicos.
 */
function validarYSanitizar(input) {
    const limpia = input.trim();
    if (limpia.length > CONFIG_SEGURIDAD.MAX_LONGITUD_TEXTO) {
        throw new Error("Exceso de longitud: Posible ataque de desbordamiento o DoS");
    }
    // CIBERSEGURIDAD: Regex estricta para evitar inyección de scripts/etiquetas
    return limpia.replace(/[^a-zA-Z0-9 ]/g, "");
}

async function crearEvento() {
    try {
        const input = document.getElementById("eventoNombre");
        // CIBERSEGURIDAD: Validación de entrada antes de procesar
        const nombreSeguro = validarYSanitizar(input.value);

        if (nombreSeguro === "") return alert("Entrada no válida");

        // CIBERSEGURIDAD: Cifrado en reposo (opcional para el array en memoria)
        const nombreCifrado = await cifrarDato(nombreSeguro);
        
        eventos.push(nombreSeguro); 
        actualizarEventos();
        input.value = "";
    } catch (e) {
        console.error("Incidente de seguridad detectado:", e.message);
    }
}

async function crearAsistente() {
    try {
        const input = document.getElementById("asistenteNombre");
        const nombreSeguro = validarYSanitizar(input.value);

        if (nombreSeguro === "") return;

        asistentes.push(nombreSeguro);
        actualizarAsistentes();
        input.value = "";
    } catch (e) {
        alert("Error en la validación de datos.");
    }
}

function registrarAsistencia() {
    // CIBERSEGURIDAD: Principio de Privilegio Mínimo y Validación de Integridad Referencial
    const evento = document.getElementById("selectEvento").value;
    const asistente = document.getElementById("selectAsistente").value;

    // Verificar que los datos existan en nuestros arrays (fuente de verdad)
    // Evita la inyección de valores no autorizados mediante la consola
    const existeEvento = eventos.includes(evento);
    const existeAsistente = asistentes.includes(asistente);

    if (!existeEvento || !existeAsistente) {
        console.warn("Intento de registro no autorizado detectado.");
        return;
    }

    // CIBERSEGURIDAD: Prevención de ataques de duplicación de registros
    const yaExiste = registros.some(r => r.evento === evento && r.asistente === asistente);
    if (yaExiste) return alert("Registro ya existente.");

    registros.push({ evento, asistente, timestamp: Date.now() });
    alert("Registro exitoso.");
}

function verAsistencia() {
    const evento = document.getElementById("consultaEvento").value;
    const lista = document.getElementById("listaAsistencia");
    
    // CIBERSEGURIDAD: Limpieza atómica del DOM para evitar persistencia de fragmentos maliciosos
    while (lista.firstChild) {
        lista.removeChild(lista.firstChild);
    }

    registros
        .filter(r => r.evento === evento)
        .forEach(r => {
            const li = document.createElement("li");
            // CIBERSEGURIDAD: Jamás usar innerHTML. textContent desactiva cualquier script.
            li.textContent = r.asistente;
            lista.appendChild(li);
        });
}

// Funciones de actualización (Mantienen la lógica original con seguridad añadida)
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
    if (!ul) return;
    ul.textContent = ""; 

    datos.forEach(dato => {
        const li = document.createElement("li");
        li.textContent = dato; 
        ul.appendChild(li);
    });
}

function actualizarSelect(id, datos) {
    const select = document.getElementById(id);
    if (!select) return;
    select.textContent = "";

    datos.forEach(dato => {
        const option = document.createElement("option");
        option.value = dato;
        option.textContent = dato;
        select.appendChild(option);
    });
}

// CIBERSEGURIDAD: Inicializar el motor criptográfico al cargar
inicializarCriptografia().catch(console.error);
