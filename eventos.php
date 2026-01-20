<?php
require "conexion.php";

$archivo = __DIR__ . "/eventos.json";
echo json_encode(leerJSON($archivo));
