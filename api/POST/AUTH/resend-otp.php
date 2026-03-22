<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . "/../../vendor/autoload.php";
$mailConfig = require __DIR__ . "/../../SECURE/mail_config.php";

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

/* ===== SMTP MAIL ===== */

$mail = new PHPMailer(true);

try {

$mail->isSMTP();
$mail->SMTPDebug = 0;

$mail->Host = $mailConfig['host'];
$mail->SMTPAuth = true;
$mail->Username = $mailConfig['username'];
$mail->Password = $mailConfig['password'];

$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = $mailConfig['port'];

$mail->SMTPOptions = [
 'ssl'=>[
  'verify_peer'=>false,
  'verify_peer_name'=>false,
  'allow_self_signed'=>true
 ]
];

$mail->setFrom(
 $mailConfig['from_email'],
 $mailConfig['from_name']
);

$mail->addAddress($user['email'], $user['full_name']);

$mail->isHTML(true);
$mail->Subject = "Login Verification Code";

$mail->Body = "
<h2>Artisan Grills Login Verification</h2>
<p>Hello {$user['full_name']},</p>
<p>Your login OTP is:</p>
<h1 style='letter-spacing:5px'>$code</h1>
<p>Expires in 5 minutes.</p>
";

$mail->send();

} catch(Exception $e){
 error_log($e->getMessage());
}

echo json_encode([
 "success"=>true,
 "message"=>"OTP sent"
]);
