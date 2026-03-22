<?php

error_reporting(1);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}


$file = __DIR__ . '/../../SECURE/db.php';

if (!file_exists($file)) {
    die(json_encode(["error" => "db.php not found"]));
}

require_once $file;



$token = $_GET['token'] ?? '';

if(!$token){
 echo json_encode(["valid"=>false]);
 exit;
}

$stmt = $conn->prepare("
SELECT id FROM user_sessions 
WHERE session_token=? 
AND expires_at > NOW()
LIMIT 1
");

$stmt->bind_param("s",$token);
$stmt->execute();

$result = $stmt->get_result();

echo json_encode([
 "valid" => $result->num_rows === 1
]);
