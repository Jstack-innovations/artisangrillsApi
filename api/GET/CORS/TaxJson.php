<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json");

$file = __DIR__ . "/../JSON/tax.json";

if (!file_exists($file)) {
    http_response_code(404);
    echo json_encode(["error" => "tax not found"]);
    exit;
}

echo file_get_contents($file);

