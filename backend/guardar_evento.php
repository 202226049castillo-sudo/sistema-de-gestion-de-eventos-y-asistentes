<?php
require "conexion.php";

$archivo = __DIR__ . "/eventos.json";
$datos = json_decode(file_get_contents("php://input"), true);

if (!$datos || empty($datos["nombre"])) {
    echo json_encode(["ok" => false]);
    exit;
}

$eventos = leerJSON($archivo);

$datos["id"] = uniqid("evt_");
$eventos[] = $datos;

guardarJSON($archivo, $eventos);
echo json_encode(["ok" => true]);
