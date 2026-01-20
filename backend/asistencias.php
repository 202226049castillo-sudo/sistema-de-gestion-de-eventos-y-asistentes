<?php
require "conexion.php";

$archivo = __DIR__ . "/asistencias.json";
echo json_encode(leerJSON($archivo));

