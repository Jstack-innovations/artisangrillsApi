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

// Get POST data
$data = json_decode(file_get_contents("php://input"), true);
$name = $data['full_name'] ?? '';
$email = $data['email'] ?? '';
$phone = $data['phone'] ?? '';
$password = $data['password'] ?? '';

// Validation
if (!$name || !$email || !$phone || !$password) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

// Check email exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Email already exists. Please use a different one.']);
    exit;
}

// Check phone exists
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

// Generate token
$token = bin2hex(random_bytes(32));

// Insert user
$stmt = $conn->prepare("INSERT INTO users 
(full_name, email, phone, password, verification_token) 
VALUES (?, ?, ?, ?, ?)");

$stmt->bind_param("sssss", $name, $email, $phone, $hashedPassword, $token);

if ($stmt->execute()) {

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
            background: #A0522D;
            color: white;
            text-align: center;
            padding: 40px 20px;
            font-size: 30px;
        }
        .content {
            padding: 20px;
            font-size: 16px;
        }
        .verify-button {
            background-color: #FF7043;
            color: #fff;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 50px;
            display: inline-block;
        }
      </style>
    </head>
    <body>
      <div class="container">
        <div class="header">Artisan Grills</div>

        <div class="content">
          <p>Hi ' . htmlspecialchars($name) . ',</p>

          <p>Welcome! Please verify your email:</p>

          <p style="text-align:center;">
            <a href="' . $verifyLink . '" class="verify-button">Verify Email</a>
          </p>

          <p>If you did not sign up, ignore this email.</p>
        </div>
      </div>
    </body>
    </html>
    ';

    // ✅ GMAIL API EMAIL SEND (ONLY CHANGE THAT MATTERS)
    sendEmail($email, $subject, $message);

    echo json_encode([
        'success' => true,
        'message' => 'User created. Please verify your email.'
    ]);

} else {
    echo json_encode([
        'success' => false,
        'message' => 'Signup failed. Please try again.'
    ]);
}
?>