<?php
class Conexion {
    private $host = "localhost";
    private $usuario = "root";
    private $password = "";
    private $base_datos = "eventos_masivos";
    private $conexion;

    public function __construct() {
        try {
            $this->conexion = new mysqli($this->host, $this->usuario, $this->password, $this->base_datos);
            
            if ($this->conexion->connect_error) {
                throw new Exception("Error de conexión: " . $this->conexion->connect_error);
            }
            
            // Establecer el conjunto de caracteres
            $this->conexion->set_charset("utf8");
            
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }

    public function getConexion() {
        return $this->conexion;
    }

    public function cerrarConexion() {
        if ($this->conexion) {
            $this->conexion->close();
        }
    }
}

// Función para obtener conexión
function obtenerConexion() {
    $conexion = new Conexion();
    return $conexion->getConexion();
}

// Función para respuestas JSON
function enviarRespuesta($success, $message, $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}
?>