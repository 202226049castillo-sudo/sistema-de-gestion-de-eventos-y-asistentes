<?php
require_once 'conectar.php';

// Obtener conexión
$conexion = obtenerConexion();

// Verificar el método de solicitud
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos del formulario
    $evento_id = $_POST['evento_id'] ?? '';
    $codigo_asistente = $_POST['codigo_asistente'] ?? '';
    
    // Validar datos
    if (empty($evento_id) || empty($codigo_asistente)) {
        enviarRespuesta(false, 'Todos los campos son obligatorios');
    }
    
    // Verificar si el asistente existe
    $query = "SELECT id FROM asistentes WHERE documento = ? OR codigo = ?";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("ss", $codigo_asistente, $codigo_asistente);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        enviarRespuesta(false, 'Asistente no encontrado. Registre primero al asistente.');
    }
    
    $asistente = $result->fetch_assoc();
    $asistente_id = $asistente['id'];
    
    // Verificar si ya está registrado en este evento
    $query = "SELECT id FROM asistencias WHERE evento_id = ? AND asistente_id = ?";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("ii", $evento_id, $asistente_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        enviarRespuesta(false, 'El asistente ya está registrado en este evento');
    }
    
    // Registrar la asistencia
    $fecha_hora = date('Y-m-d H:i:s');
    $query = "INSERT INTO asistencias (evento_id, asistente_id, fecha_hora) VALUES (?, ?, ?)";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("iis", $evento_id, $asistente_id, $fecha_hora);
    
    if ($stmt->execute()) {
        enviarRespuesta(true, 'Asistencia registrada exitosamente');
    } else {
        enviarRespuesta(false, 'Error al registrar la asistencia: ' . $stmt->error);
    }
    
} else {
    enviarRespuesta(false, 'Método no permitido');
}

$conexion->close();
?>