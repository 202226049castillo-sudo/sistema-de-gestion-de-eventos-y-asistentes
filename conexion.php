<?php
// Evitamos el acceso directo al archivo por URL para proteger la lógica
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    header("HTTP/1.1 403 Forbidden");
    exit;
}

header("Content-Type: application/json; charset=UTF-8");

/**
 * Lee un archivo JSON con validación de integridad.
 */
function leerJSON($archivo) {
    // 1. Verificación de existencia y permisos
    if (!file_exists($archivo) || !is_readable($archivo)) {
        return [];
    }

    $contenido = file_get_contents($archivo);
    if ($contenido === false) return [];

    // 2. Validación de formato JSON para evitar inyección de datos corruptos
    $data = json_decode($contenido, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Error JSON en $archivo: " . json_last_error_msg());
        return [];
    }

    return $data;
}

/**
 * Guarda datos en JSON usando bloqueo de archivo (Locking).
 */
function guardarJSON($archivo, $data) {
    // 3. Flags de seguridad: 
    // LOCK_EX evita que dos usuarios escriban al mismo tiempo (corrupción de datos)
    $json_data = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    if ($json_data === false) {
        return false;
    }

    // 4. Escritura atómica para prevenir archivos vacíos si falla el servidor
    return file_put_contents($archivo, $json_data, LOCK_EX);
}
