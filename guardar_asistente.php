<?php
/**
 * 1. Encabezados de Seguridad
 */
header("Content-Type: application/json; charset=UTF-8");
header("X-Content-Type-Options: nosniff");
header("Access-Control-Allow-Methods: POST");

require_once "conexion.php";

// Solo permitir peticiones POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["ok" => false, "error" => "Método no permitido"]);
    exit;
}

/**
 * 2. Lectura Segura del Input
 */
$input = file_get_contents("php://input");

// Limitar el tamaño del nombre para evitar ataques de saturación (DoS)
if (strlen($input) > 1024) {
    http_response_code(413);
    echo json_encode(["ok" => false, "error" => "Carga útil demasiado grande"]);
    exit;
}

$datos = json_decode($input, true);

// Validación de existencia y tipo de dato
if (!$datos || empty($datos["nombre"]) || !is_string($datos["nombre"])) {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "Nombre inválido o ausente"]);
    exit;
}

/**
 * 3. Sanitización (Prevención de XSS)
 */
// Eliminamos etiquetas HTML y convertimos caracteres especiales
$nombreLimpio = htmlspecialchars(strip_tags(trim($datos["nombre"])), ENT_QUOTES, 'UTF-8');

// Validar que tras la limpieza el nombre no haya quedado vacío
if (empty($nombreLimpio)) {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "El nombre contiene caracteres no permitidos"]);
    exit;
}

/**
 * 4. Persistencia Segura
 */
$archivo = __DIR__ . "/asistentes.json";

try {
    $asistentes = leerJSON($archivo);
    
    // Crear el nuevo objeto asistente con un ID único o timestamp
    $nuevoAsistente = [
        "id" => uniqid(),
        "nombre" => $nombreLimpio,
        "fecha_registro" => date("c")
    ];

    $asistentes[] = $nuevoAsistente;

    if (guardarJSON($archivo, $asistentes)) {
        echo json_encode(["ok" => true, "id" => $nuevoAsistente["id"]]);
    } else {
        throw new Exception("Error al escribir en disco");
    }

} catch (Exception $e) {
    error_log("Error en guardar_asistente: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["ok" => false, "error" => "Error interno del servidor"]);
}
