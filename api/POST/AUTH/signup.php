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



// Get POST data
$data = json_decode(file_get_contents("php://input"), true);
$name = $data['full_name'] ?? '';
$email = $data['email'] ?? '';
$phone = $data['phone'] ?? '';
$password = $data['password'] ?? '';

// Validation: basic check
if (!$name || !$email || !$phone || !$password) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

// Check if email exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Email already exists. Please use a different one.']);
    exit;
}

// Check if phone exists
$stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
$stmt->bind_param("s", $phone);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Phone number already exists. Please use a different one.']);
    exit;
}

// Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Generate verification token
$token = bin2hex(random_bytes(32));

// Insert new user
$stmt = $conn->prepare("INSERT INTO users 
(full_name, email, phone, password, verification_token) 
VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $name, $email, $phone, $hashedPassword, $token);

if($stmt->execute()){
    //$verifyLink = "https://artisangrills-production.up.railway.app/verify?token=" . $token;
    $scheme = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    $_SERVER['SERVER_PORT'] == 443
) ? "https://" : "http://";

$host = $_SERVER['HTTP_HOST'];

$baseUrl = $scheme . $host;

$verifyLink = $baseUrl . "/verify?token=" . $token;
    
    
$subject = "Welcome to Artisan Grills! Verify Your Email";

    $message = '
    <html>
    <head>
      <style>
        @import url("https://fonts.googleapis.com/css2?family=Sacramento&display=swap");
        body {
            margin:0;
            padding:0;
            font-family: Arial, sans-serif;
            background-color: #fff8f0;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .header {
            position: relative;
            background: #A0522D;
            color: white;
            text-align: center;
            padding: 40px 20px 20px 20px;
            font-family: "Sacramento", cursive;
            font-size: 36px;
            overflow: hidden;
        }
        /* Smoke animation behind header text */
        .smoke {
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 200px;
            height: 100px;
            background: url("https://jstack-sigma.vercel.app/artisangrill/smoke.png") no-repeat center;
            background-size: contain;
            opacity: 0.2;
            animation: rise 6s linear infinite;
        }
        @keyframes rise {
            0% { transform: translateX(-50%) translateY(20px); opacity: 0.2; }
            50% { transform: translateX(-50%) translateY(-10px); opacity: 0.3; }
            100% { transform: translateX(-50%) translateY(-50px); opacity: 0; }
        }

        .subheader {
            text-align: center;
            color: #fff8e1;
            font-size: 16px;
            margin-bottom: 20px;
        }
        .content {
            padding: 20px 30px;
            font-size: 16px;
            line-height: 1.5;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .verify-button {
            background-color: #FF7043;
            color: #fff;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 50px;
            font-weight: bold;
            display: inline-block;
            transition: background 0.3s ease;
        }
        .verify-button:hover {
            background-color: #E64A19;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #999;
            padding: 20px;
        }
        .hero-img, .food-gif {
            display: block;
            margin: 0 auto 15px auto;
        }
        .hero-img {
            width: 100px;
            animation: float 3s ease-in-out infinite;
        }
        .food-gif {
            width: 60px;
            animation: float 2s ease-in-out infinite alternate;
        }
        @keyframes float {
          0% { transform: translatey(0px); }
          50% { transform: translatey(-10px); }
          100% { transform: translatey(0px); }
        }
      </style>
    </head>
    <body>
      <div class="container">
        <div class="header">
          <div class="smoke"></div>
          Artisan Grills
        </div>
        <div class="subheader">Curated Packages Just for You</div>

        <!-- Hero image -->
        <img src="https://jstack-sigma.vercel.app/artisangrill/toon-chef.png" class="hero-img" alt="Chef Hat" />

        <div class="content">
          <p>Hi ' . htmlspecialchars($name) . ',</p>
          <p>Welcome to <strong>Artisan Grills</strong>! We are thrilled to have you on board.</p>
          <p>Click the button below to verify your email and activate your account:</p>

          <div class="button-container">
            <a href="' . $verifyLink . '" class="verify-button">Verify Email</a>
          </div>

          <p>Enjoy your dining journey! Here are some of our favorites:</p>
          <div style="text-align:center;">
            <img src="https://jstack-sigma.vercel.app/artisangrill/pizza.png" class="food-gif" alt="Pizza" />
            <img src="https://jstack-sigma.vercel.app/artisangrill/burger.png" class="food-gif" alt="Burger" />
            <img src="https://jstack-sigma.vercel.app/artisangrill/grill.png" class="food-gif" alt="Grill" />
          </div>

          <p>If you did not sign up for Artisan Grills, please ignore this email.</p>
        </div>

        <div class="footer">
          Artisan Grills | Curated dining experiences | &copy; ' . date("Y") . '
        </div>
      </div>
    </body>
    </html>
    ';

$mail = new PHPMailer(true);

try {

        $mail->isSMTP();
    $mail->Timeout = 10;
    $mail->SMTPDebug = 0;

    $mail->Host = $mailConfig['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $mailConfig['username'];
    $mail->Password = $mailConfig['password'];

    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $mailConfig['port'];

    // REMOVE IN PRODUCTION
   /*$mail->SMTPOptions = [
        'ssl'=>[
            'verify_peer'=>false,
            'verify_peer_name'=>false,
            'allow_self_signed'=>true
        ]
    ];*/

    $mail->setFrom(
        $mailConfig['from_email'],
        $mailConfig['from_name']
    );

    $mail->addAddress($email, $name);

    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $message;

    if(!$mail->send()){
        echo json_encode([
            "success"=>false,
            "message"=>"Account created but verification email failed."
        ]);
        exit;
    }

} catch(Exception $e){
    echo json_encode([
        "success"=>false,
        "message"=>"Mail server error: " . $e->getMessage()
    ]);
    exit;
}


    echo json_encode(['success'=>true,'message'=>'User created. Please verify your email.']);
}else {
    echo json_encode(['success'=>false,'message'=>'Signup failed. Please try again.']);
}
?>
