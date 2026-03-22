<?php

require_once __DIR__ . '/../../SECURE/gmail_mailer.php';

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

$email = $data['email'] ?? '';

if(!$email){
    echo json_encode(["success"=>false,"message"=>"Email required"]);
    exit;
}

$stmt = $conn->prepare("SELECT id,email,full_name FROM users WHERE email=?");
$stmt->bind_param("s",$email);
$stmt->execute();

$result = $stmt->get_result();

if($result->num_rows !== 1){
    echo json_encode(["success"=>false,"message"=>"User not found"]);
    exit;
}

$user = $result->fetch_assoc();

$user_id = $user['id'];

$code = rand(1000,9999);
$expires = date("Y-m-d H:i:s", strtotime("+5 minutes"));

$conn->query("DELETE FROM login_verifications WHERE user_id=$user_id");

$insert = $conn->prepare("
INSERT INTO login_verifications(user_id,code,expires_at)
VALUES(?,?,?)
");

$insert->bind_param("iss",$user_id,$code,$expires);
$insert->execute();

/* =========================
   GMAIL API SEND
========================= */

$to = $user['email'];
$subject = "Login Verification Code";

$message = "
<h2>Artisan Grills Login Verification</h2>

<p>Hello {$user['full_name']},</p>

<p>Your login OTP is:</p>

<h1 style='letter-spacing:5px'>{$code}</h1>

<p>Expires in 5 minutes.</p>
";

$result = sendEmail($to, $subject, $message);

if (!$result) {
    echo json_encode([
        "success" => false,
        "message" => "OTP sending failed"
    ]);
    exit;
}

echo json_encode([
    "success"=>true,
    "message"=>"OTP sent"
]);
?>
