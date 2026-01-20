<?php
/**
 * 1. Encabezados de Seguridad y Control de Acceso
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
 * 2. Validación de Entrada (Input Handling)
 */
$input = file_get_contents("php://input");

// Limitar el tamaño del payload a 5KB para prevenir ataques DoS
if (strlen($input) > 5120) {
    http_response_code(413);
    echo json_encode(["ok" => false, "error" => "Petición demasiado pesada"]);
    exit;
}

$datos = json_decode($input, true);

// Validación de campos obligatorios y tipos de datos
if (!$datos || empty($datos["nombre"]) || !is_string($datos["nombre"])) {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "El nombre del evento es obligatorio y debe ser texto"]);
    exit;
}

/**
 * 3. Sanitización y Construcción del Objeto (Prevención de XSS)
 */
$nombreLimpio = htmlspecialchars(strip_tags(trim($datos["nombre"])), ENT_QUOTES, 'UTF-8');

// Verificamos si hay una descripción y la sanitizamos también
$descripcionLimpia = isset($datos["descripcion"]) 
    ? htmlspecialchars(strip_tags(trim($datos["descripcion"])), ENT_QUOTES, 'UTF-8') 
    : "";

/**
 * 4. Persistencia Segura
 */
$archivo = __DIR__ . "/eventos.json";

try {
    $eventos = leerJSON($archivo);
    
    // Estructuramos el nuevo evento con datos validados
    $nuevoEvento = [
        "id" => uniqid("evt_"),
        "nombre" => $nombreLimpio,
        "descripcion" => $descripcionLimpia,
        "fecha_creacion" => date("c"),
        "ip_registro" => $_SERVER['REMOTE_ADDR'] // Opcional: Para trazabilidad de seguridad
    ];

    $eventos[] = $nuevoEvento;

    if (guardarJSON($archivo, $eventos)) {
        echo json_encode(["ok" => true, "id" => $nuevoEvento["id"]]);
    } else {
        throw new Exception("Error de escritura en disco");
    }

} catch (Exception $e) {
    error_log("Error en guardar_evento: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["ok" => false, "error" => "Error interno del servidor"]);
}
