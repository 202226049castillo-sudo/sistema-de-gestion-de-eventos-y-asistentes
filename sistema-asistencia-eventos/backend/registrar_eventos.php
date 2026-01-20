<?php
require_once 'conectar.php';

// Obtener conexión
$conexion = obtenerConexion();

// Verificar el método de solicitud
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos del formulario
    $nombre = $_POST['nombre_evento'] ?? '';
    $fecha = $_POST['fecha_evento'] ?? '';
    $ubicacion = $_POST['ubicacion'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    
    // Validar datos obligatorios
    if (empty($nombre) || empty($fecha) || empty($ubicacion)) {
        enviarRespuesta(false, 'Nombre, fecha y ubicación son obligatorios');
    }
    
    // Insertar nuevo evento
    $query = "INSERT INTO eventos (nombre, fecha, ubicacion, descripcion, fecha_creacion) 
              VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("ssss", $nombre, $fecha, $ubicacion, $descripcion);
    
    if ($stmt->execute()) {
        enviarRespuesta(true, 'Evento registrado exitosamente');
    } else {
        enviarRespuesta(false, 'Error al registrar el evento: ' . $stmt->error);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['accion']) && $_GET['accion'] === 'listar') {
    // Listar todos los eventos activos
    $query = "SELECT id, nombre, DATE_FORMAT(fecha, '%d/%m/%Y') as fecha, ubicacion 
              FROM eventos 
              WHERE fecha >= CURDATE() 
              ORDER BY fecha ASC";
    $result = $conexion->query($query);
    
    $eventos = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $eventos[] = $row;
        }
        enviarRespuesta(true, 'Eventos obtenidos', ['eventos' => $eventos]);
    } else {
        enviarRespuesta(true, 'No hay eventos disponibles', ['eventos' => []]);
    }
} else {
    enviarRespuesta(false, 'Método no permitido');
}

$conexion->close();
?>