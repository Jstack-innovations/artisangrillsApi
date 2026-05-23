<?php
/**
 * Enflow Account Status API
 * Endpoint: GET /api/accountStatus
 * Returns: name, email, plan, status, dates, zara credits
 */

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../SECURE/config.php';
session_start();

// ── Auth check — must be logged in ──
if (empty($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized."]);
    exit();
}

$userId = (int) $_SESSION["user_id"];

// ── Fetch user row ──
$stmt = $pdo->prepare("
    SELECT name, email, selected_plan, trial_started_at, trial_ends_at,
           is_subscribed, subscription_plan, subscription_start, subscription_end,
           zara_credits, zara_credits_used
    FROM enflow_users
    WHERE id = :id
    LIMIT 1
");
$stmt->execute([":id" => $userId]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "User not found."]);
    exit();
}

$now = new DateTime();

// ── Determine account status ──
if ($user["is_subscribed"] && $user["subscription_end"]) {
    $subEnd = new DateTime($user["subscription_end"]);
    $accountStatus = $subEnd > $now ? "active" : "expired";
} elseif ($user["trial_ends_at"]) {
    $trialEnd = new DateTime($user["trial_ends_at"]);
    $accountStatus = $trialEnd > $now ? "trial" : "expired";
} else {
    $accountStatus = "none";
}

echo json_encode([
    "name"               => $user["name"],
    "email"              => $user["email"],
    "plan"               => $user["is_subscribed"] ? $user["subscription_plan"] : $user["selected_plan"],
    "status"             => $accountStatus,
    "trial_ends_at"      => $user["trial_ends_at"],
    "subscription_start" => $user["subscription_start"],
    "subscription_end"   => $user["subscription_end"],
    "zara_credits"       => (int) ($user["zara_credits"] ?? 1000),
    "zara_credits_used"  => (int) ($user["zara_credits_used"] ?? 0),
]);

