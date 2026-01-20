<?php
require_once 'conectar.php';

header('Content-Type: application/json');

try {
    $conexion = new ConexionSegura();
    $db = $conexion->getConexion();
    
    // Verificar conexión a la base de datos
    $stmt = $db->query("SELECT 1 as status");
    $result = $stmt->fetch();
    
    $response = [
        'status' => 'online',
        'timestamp' => date('Y-m-d H:i:s'),
        'db_connected' => $result && $result['status'] == 1,
        'server_time' => time(),
        'memory_usage' => memory_get_usage(true),
        'uptime' => @file_get_contents('/proc/uptime') ?: 'unknown'
    ];
    
    // Verificar servicios adicionales si es necesario
    $response['services'] = [
        'database' => true,
        'session' => session_status() === PHP_SESSION_ACTIVE,
        'cache' => function_exists('apcu_enabled') && apcu_enabled()
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(503);
    echo json_encode([
        'status' => 'offline',
        'timestamp' => date('Y-m-d H:i:s'),
        'error' => 'Service unavailable',
        'db_connected' => false
    ]);
}
?>
