<?php
header("Content-Type: application/json; charset=UTF-8");

function leerJSON($archivo) {
    if (!file_exists($archivo)) return [];

}

function guardarJSON($archivo, $data) {
    file_put_contents($archivo, json_encode($data, JSON_PRETTY_PRINT));
}
