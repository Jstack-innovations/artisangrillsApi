<?php
// Send JSON headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// OPTIONS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$data = file_get_contents("php://input");

$filePath = __DIR__ . "/../../GET/JSON/offers.json";

file_put_contents($filePath, $data);

echo json_encode(["status" => "success"]);
?>
