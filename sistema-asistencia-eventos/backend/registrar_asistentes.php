<?php
require_once 'conectar.php';

// Obtener conexión
$conexion = obtenerConexion();

// Verificar el método de solicitud
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos del formulario
    $nombre = $_POST['nombre'] ?? '';
    $documento = $_POST['documento'] ?? '';
    $email = $_POST['email'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    
    // Validar datos obligatorios
    if (empty($nombre) || empty($documento)) {
        enviarRespuesta(false, 'Nombre y documento son obligatorios');
    }
    
    // Verificar si el asistente ya existe
    $query = "SELECT id FROM asistentes WHERE documento = ?";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("s", $documento);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        enviarRespuesta(false, 'El asistente con este documento ya está registrado');
    }
    
    // Generar código único
    $codigo = 'ASIS-' . strtoupper(substr(md5(uniqid()), 0, 8));
    
    // Insertar nuevo asistente
    $query = "INSERT INTO asistentes (nombre, documento, email, telefono, codigo, fecha_registro) 
              VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("sssss", $nombre, $documento, $email, $telefono, $codigo);
    
    if ($stmt->execute()) {
        $data = [
            'codigo' => $codigo,
            'id' => $stmt->insert_id
        ];
        enviarRespuesta(true, 'Asistente registrado exitosamente. Código: ' . $codigo, $data);
    } else {
        enviarRespuesta(false, 'Error al registrar el asistente: ' . $stmt->error);
    }
    
} else {
    enviarRespuesta(false, 'Método no permitido');
}

$conexion->close();
?>