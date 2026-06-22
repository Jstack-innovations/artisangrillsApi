<?php
require_once __DIR__ . "/../../SECURE/authGuard.php";
require_once __DIR__ . "/../../SECURE/centralProxy.php";

$adminId = $GLOBALS['admin_id'];

$stmt = $conn->prepare("SELECT email FROM admins WHERE id = ?");
$stmt->bind_param("i", $adminId);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$email = $admin["email"] ?? "";

if (!$email) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Not authenticated."]);
    exit();
}

$raw  = file_get_contents("php://input");
$data = json_decode($raw, true) ?? [];
$data["email"] = $email;

$ch = curl_init(CENTRAL_SERVER . "/zaraTopup");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_TIMEOUT        => 20,
    CURLOPT_HTTPHEADER     => ["Content-Type: application/json"],
    CURLOPT_POSTFIELDS     => json_encode($data),
]);
$res  = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

http_response_code($code);
echo $res;
