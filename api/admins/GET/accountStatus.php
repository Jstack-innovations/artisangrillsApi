<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") { http_response_code(200); exit(); }

require_once __DIR__ . '/../../SECURE/config.php';

// ── Identify user by token (sent from frontend after signup/login) ──
$token = trim($_GET["token"] ?? $_SERVER["HTTP_X_USER_TOKEN"] ?? "");

if (!$token) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "No token provided."]);
    exit();
}

$stmt = $pdo->prepare("
    SELECT id, name, email, phone, plan, status,
           trial_started_at, trial_ends_at,
           renewal_date, subscription_code,
           zara_credits, zara_credits_used, amount, created_at
    FROM subscriptions
    WHERE onboarding_token = :token
    LIMIT 1
");
$stmt->execute([":token" => $token]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "User not found."]);
    exit();
}

$now        = new DateTime();
$status     = $user["status"];

// ── Auto-expire trial if time has passed ──
if ($status === "trial" && $user["trial_ends_at"]) {
    $trialEnd = new DateTime($user["trial_ends_at"]);
    if ($trialEnd <= $now) {
        // Mark expired in DB
        $pdo->prepare("UPDATE subscriptions SET status = 'expired' WHERE id = :id")
            ->execute([":id" => $user["id"]]);
        $status = "expired";
    }
}

// ── Auto-expire active subscription if renewal passed ──
if ($status === "active" && $user["renewal_date"]) {
    $renewalDate = new DateTime($user["renewal_date"]);
    if ($renewalDate <= $now) {
        $pdo->prepare("UPDATE subscriptions SET status = 'expired' WHERE id = :id")
            ->execute([":id" => $user["id"]]);
        $status = "expired";
    }
}

echo json_encode([
    "name"               => $user["name"],
    "email"              => $user["email"],
    "phone"              => $user["phone"],
    "plan"               => $user["plan"],
    "status"             => $status,           // trial | active | expired
    "trial_started_at"   => $user["trial_started_at"],
    "trial_ends_at"      => $user["trial_ends_at"],
    "subscription_start" => $user["created_at"],
    "renewal_date"       => $user["renewal_date"],
    "subscription_code"  => $user["subscription_code"],
    "amount_paid"        => $user["amount"],
    "zara_credits"       => (int) $user["zara_credits"],
    "zara_credits_used"  => (int) $user["zara_credits_used"],
    "zara_credits_left"  => (int) $user["zara_credits"] - (int) $user["zara_credits_used"],
]);
