<?php
/**
 * 1. Cabeceras de Seguridad y Control de Tipo de Contenido
 */
// Define explícitamente que la respuesta es JSON y usa UTF-8
header("Content-Type: application/json; charset=UTF-8");
// Evita el "MIME Sniffing" (previene que el navegador ejecute contenido malicioso)
header("X-Content-Type-Options: nosniff");
// Restringe que el contenido sea embebido en otros sitios (prevención de Clickjacking)
header("X-Frame-Options: DENY");

// Opcional: Solo permitir peticiones desde tu propio dominio
// header("Access-Control-Allow-Origin: https://tusitio.com");

/**
 * 2. Inclusión Segura de Dependencias
 */
// Usamos require_once para evitar errores de redifinicón de funciones
require_once "conexion.php";

/**
 * 3. Lógica de Negocio con Manejo de Errores
 */
$archivo = __DIR__ . "/eventos.json";

try {
    // Verificamos la existencia del archivo antes de procesar
    if (!file_exists($archivo)) {
        http_response_code(404);
        echo json_encode([
            "status" => "error",
            "message" => "El catálogo de eventos no está disponible"
        ]);
        exit;
    }

    // Leemos los datos usando la función segura definida en conexion.php
    $eventos = leerJSON($archivo);

    // Si el archivo está vacío o corrupto, enviamos una respuesta controlada
    if ($eventos === null) {
        throw new Exception("Error en el formato de datos");
    }

    // Respuesta exitosa
    echo json_encode($eventos);

} catch (Exception $e) {
    // Log del error real para el administrador (servidor)
    error_log("Error en eventos.php: " . $e->getMessage());

    // Respuesta genérica para el usuario (seguridad por oscuridad)
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error interno al procesar la solicitud"
    ]);
}
