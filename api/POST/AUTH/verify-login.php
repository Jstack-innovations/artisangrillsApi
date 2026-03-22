<?php
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



$data = json_decode(file_get_contents("php://input"), true);

$user_id = $data['user_id'] ?? '';
$code = $data['code'] ?? '';

$stmt = $conn->prepare("
SELECT * FROM login_verifications
WHERE user_id=?
LIMIT 1
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    echo json_encode(["success"=>false,"message"=>"Code expired"]);
    exit;
}

$row = $result->fetch_assoc();

/* Expiry check */
if (strtotime($row['expires_at']) < time()) {
    echo json_encode(["success"=>false,"message"=>"Code expired"]);
    exit;
}

/* Attempt limit */
if ($row['attempts'] >= 5) {
    echo json_encode(["success"=>false,"message"=>"Too many attempts"]);
    exit;
}

/* Wrong code */
if ($row['code'] !== $code) {
    $conn->query("UPDATE login_verifications SET attempts = attempts + 1 WHERE user_id=$user_id");
    echo json_encode(["success"=>false,"message"=>"Invalid code"]);
    exit;
}

/*
==============================
NOW CREATE SESSION
==============================
*/

$session_token = bin2hex(random_bytes(32));
$expires_at = date("Y-m-d H:i:s", strtotime("+30 minutes"));

$insert = $conn->prepare("
INSERT INTO user_sessions (user_id, session_token, expires_at)
VALUES (?,?,?)
");

$insert->bind_param("iss",
    $user_id,
    $session_token,
    $expires_at
);

$insert->execute();

/* Delete OTP */
$conn->query("DELETE FROM login_verifications WHERE user_id=$user_id");

/* Fetch user */
$stmt = $conn->prepare("SELECT id, full_name, email, phone FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

echo json_encode([
    "success"=>true,
    "session_token"=>$session_token,
    "expires_at"=>$expires_at,
    "user"=>$user
]);
