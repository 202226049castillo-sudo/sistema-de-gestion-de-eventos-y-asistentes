<?php
// 1. Configuración de seguridad en los encabezados
header("Content-Type: application/json; charset=UTF-8");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("Access-Control-Allow-Methods: GET");

// 2. Restricción de acceso por IP o Dominio (Opcional pero recomendado)
// header("Access-Control-Allow-Origin: https://tusitio.com");

require_once "conexion.php";

/**
 * Función para leer JSON de forma segura
 */
function leerJSONSeguro($path) {
    // Validar que el archivo existe antes de intentar leerlo
    if (!file_exists($path)) {
        http_response_code(404);
        return ["error" => "Recurso no encontrado"];
    }

    // Validar que el archivo sea un .json y no otro tipo de archivo sensible
    if (pathinfo($path, PATHINFO_EXTENSION) !== 'json') {
        http_response_code(403);
        return ["error" => "Tipo de archivo no permitido"];
    }

    $contenido = file_get_contents($path);
    
    if ($contenido === false) {
        http_response_code(500);
        return ["error" => "Error al leer los datos"];
    }

    $data = json_decode($contenido, true);

    // Validar si el JSON es válido
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(500);
        return ["error" => "Formato de datos corrupto"];
    }

    return $data;
}

// 3. Uso de rutas relativas controladas para evitar Path Traversal
$archivo = __DIR__ . "/asistencias.json";

// 4. Salida controlada
try {
    echo json_encode(leerJSONSeguro($archivo));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error interno del servidor"]);
}
