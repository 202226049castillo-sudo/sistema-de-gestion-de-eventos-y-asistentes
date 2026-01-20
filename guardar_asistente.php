<?php
require "conexion.php";

$archivo = __DIR__ . "/asistentes.json";
$datos = json_decode(file_get_contents("php://input"), true);

if (!$datos || empty($datos["nombre"])) {
    echo json_encode(["ok" => false]);
    exit;
}

$asistentes = leerJSON($archivo);

$datos["id"] = uniqid("asis_");
$asistentes[] = $datos; 

guardarJSON($archivo, $asistentes);
echo json_encode(["ok" => true]);

