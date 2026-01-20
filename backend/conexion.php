<<<<<<< HEAD
<?php
header("Content-Type: application/json; charset=UTF-8");

function leerJSON($archivo) {
    if (!file_exists($archivo)) return [];
    $contenido = file_get_contents($archivo);
    return $contenido ? json_decode($contenido, true) : [];
}

function guardarJSON($archivo, $data) {
    file_put_contents($archivo, json_encode($data, JSON_PRETTY_PRINT));
}

=======
<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "gestion_eventos";

$conn =new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Error de conexión: ". $conn->connect_error);
}
?>
>>>>>>> f1853e8 (Estructura del proyecto y archivos frontend/backend iniciales)
