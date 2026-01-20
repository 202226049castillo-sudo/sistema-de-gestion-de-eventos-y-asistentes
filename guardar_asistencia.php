<?php
require "conexion.php";

$archivo = __DIR__ . "/asistencias.json";
$datos = json_decode(file_get_contents("php://input"), true);

if (
    !$datos ||
    empty($datos["eventoId"]) ||
    empty($datos["asistenteId"])
) {
    echo json_encode(["ok" => false]);
    exit;
}

$asistencias = leerJSON($archivo);



$asistencias[] = $datos;

guardarJSON($archivo, $asistencias);
echo json_encode(["ok" => true]);
