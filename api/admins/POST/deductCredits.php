<?php
require_once __DIR__ . "/../../SECURE/authGuard.php";
require_once __DIR__ . "/../../SECURE/config.php";

$email = $_SESSION["admin_email"] ?? "";
$data  = json_decode(file_get_contents("php://input"), true);
$amount = (int)($data["credits"] ?? 1);

// Check and deduct in one query — only deducts if credits available
$stmt = $pdo->prepare("
    UPDATE subscriptions
    SET zara_credits_used = zara_credits_used + :amount
    WHERE LOWER(email) = LOWER(:email)
    AND (zara_credits - zara_credits_used) >= :amount2
");
$stmt->execute([
    ":amount"  => $amount,
    ":amount2" => $amount,
    ":email"   => $email,
]);

if ($stmt->rowCount() === 0) {
    echo json_encode(["status" => "error", "message" => "Insufficient Zara credits"]);
    exit;
}

echo json_encode(["status" => "success"]);
