<?php
/**
 * 1. Configuración de Seguridad y Control de Acceso
 */
header("Content-Type: application/json; charset=UTF-8");
header("X-Content-Type-Options: nosniff");
header("Access-Control-Allow-Methods: POST"); // Solo permitir POST

require_once "conexion.php";

// Solo procesar peticiones POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["ok" => false, "error" => "Método no permitido"]);
    exit;
}

/**
 * 2. Validación de Entrada (Input Sanitization)
 */
$input = file_get_contents("php://input");

// Limitar el tamaño del cuerpo de la petición (ej. 2KB) para evitar ataques DoS
if (strlen($input) > 2048) {
    http_response_code(413);
    echo json_encode(["ok" => false, "error" => "Petición demasiado pesada"]);
    exit;
}

$datos = json_decode($input, true);

// Validación estricta de campos y tipos de datos
if (
    !isset($datos["eventoId"]) || !is_scalar($datos["eventoId"]) ||
    !isset($datos["asistenteId"]) || !is_scalar($datos["asistenteId"])
) {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "Datos incompletos o inválidos"]);
    exit;
}

/**
 * 3. Sanitización y Preparación de Datos
 */
$nuevaAsistencia = [
    "eventoId"    => htmlspecialchars(strip_tags($datos["eventoId"])),
    "asistenteId" => htmlspecialchars(strip_tags($datos["asistenteId"])),
    "fecha_registro" => date("Y-m-d H:i:s") // Añadimos timestamp interno
];

$archivo = __DIR__ . "/asistencias.json";

/**
 * 4. Operación Segura de Escritura
 */
try {
    $asistencias = leerJSON($archivo);
    
    // Evitar duplicados (Seguridad lógica)
    foreach ($asistencias as $asistencia) {
        if ($asistencia['eventoId'] === $nuevaAsistencia['eventoId'] && 
            $asistencia['asistenteId'] === $nuevaAsistencia['asistenteId']) {
            http_response_code(409);
            echo json_encode(["ok" => false, "error" => "El asistente ya está registrado"]);
            exit;
        }
    }

    $asistencias[] = $nuevaAsistencia;

    if (guardarJSON($archivo, $asistencias)) {
        echo json_encode(["ok" => true, "mensaje" => "Asistencia registrada"]);
    } else {
        throw new Exception("Error al escribir en el disco");
    }

} catch (Exception $e) {
    error_log("Error crítico en guardar_asistencia: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["ok" => false, "error" => "Error interno al guardar"]);
}
