<?php
require "conexion.php";

$archivo = __DIR__ . "/asistentes.json";
echo json_encode(leerJSON($archivo));

