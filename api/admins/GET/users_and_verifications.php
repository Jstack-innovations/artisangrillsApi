<?php

// Allow JSON + CORS
$allowedOrigins = [
    "http://localhost:5173",
    "https://artisangrills-production.up.railway.app",
    "https://admin-artisangrilluxe.vercel.app"
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
}

header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// OPTIONS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();

$file = __DIR__ . '/../../SECURE/db.php';

if (!file_exists($file)) {
    die(json_encode(["error" => "db.php not found"]));
}

require_once $file;

$data = [
"users" => [],
"verifications" => []
];

/* USERS */
$q = $conn->query("SELECT id,full_name,email,phone,status,created_at FROM users ORDER BY id DESC");

while($row = $q->fetch_assoc()){
$data["users"][] = $row;
}

/* VERIFICATIONS */
$q2 = $conn->query("
SELECT lv.*, u.full_name, u.email
FROM login_verifications lv
LEFT JOIN users u ON u.id = lv.user_id
ORDER BY lv.id DESC
");

while($row = $q2->fetch_assoc()){
$data["verifications"][] = $row;
}

echo json_encode($data);
