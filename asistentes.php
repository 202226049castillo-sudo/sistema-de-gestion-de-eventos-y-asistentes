<?php
/**
 * Seguridad de Nivel 1: Control de Acceso y Cabeceras
 */
// Forzar que la respuesta sea siempre JSON
header("Content-Type: application/json; charset=UTF-8");
// Evitar que el navegador intente adivinar el tipo de contenido (previene XSS)
header("X-Content-Type-Options: nosniff");
// Impedir que el sitio sea cargado en iframes (previene Clickjacking)
header("X-Frame-Options: DENY");

require_once "conexion.php";

/**
 * Seguridad de Nivel 2: Validación de Entorno y Variables
 */
// Definir la ruta de forma absoluta y segura
$archivo = __DIR__ . "/asistencias.json";

// Verificar que el archivo existe y es legible antes de procesar
if (!file_exists($archivo) || !is_readable($archivo)) {
    http_response_code(404);
    echo json_encode([
        "error" => "Recurso no disponible",
        "code" => 404
    ]);
    exit;
}

/**
 * Seguridad de Nivel 3: Manejo de Errores y Salida
 */
try {
    // Se asume que leerJSON() está definida en conexion.php
    $datos = leerJSON($archivo);

    if ($datos === null) {
        throw new Exception("Error al decodificar los datos.");
    }

    echo json_encode($datos);

} catch (Exception $e) {
    // No mostramos el mensaje real de la excepción ($e->getMessage()) 
    // para no revelar detalles técnicos al atacante.
    http_response_code(500);
    echo json_encode([
        "error" => "Error interno del servidor",
        "code" => 500
    ]);
}
