<?php
// POST /zara/topup/verify
require_once __DIR__ . "/../../SECURE/authGuard.php";
require_once __DIR__ . "/../../SECURE/config.php";

$email = $_SESSION["admin_email"] ?? "";

if (!$email) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Not authenticated."]);
    exit();
}

$body = json_decode(file_get_contents("php://input"), true);

$tx_ref        = trim($body["tx_ref"] ?? "");
$transaction_id = trim($body["transaction_id"] ?? "");
$pack_id       = trim($body["pack_id"] ?? "");
$credits       = (int) ($body["credits"] ?? 0);

if (!$tx_ref || !$transaction_id || !$pack_id || $credits <= 0) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Missing required fields."]);
    exit();
}

// ── Expected pack prices (server-side validation) ──────────
$PACKS = [
    "starter"    => ["credits" => 500,   "price" => 52250],
    "basic"      => ["credits" => 1000,  "price" => 101200],
    "standard"   => ["credits" => 2500,  "price" => 242000],
    "popular"    => ["credits" => 3000,  "price" => 280500],
    "pro"        => ["credits" => 5000,  "price" => 451000],
    "business"   => ["credits" => 7000,  "price" => 600600],
    "enterprise" => ["credits" => 10000, "price" => 825000],
];

if (!isset($PACKS[$pack_id])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid pack."]);
    exit();
}

$expectedCredits = $PACKS[$pack_id]["credits"];
$expectedPrice   = $PACKS[$pack_id]["price"];

// ── Prevent duplicate processing ──────────────────────────
$check = $pdo->prepare("SELECT id FROM zara_topup_logs WHERE tx_ref = :tx_ref LIMIT 1");
$check->execute([":tx_ref" => $tx_ref]);
if ($check->fetch()) {
    http_response_code(409);
    echo json_encode(["status" => "error", "message" => "Transaction already processed."]);
    exit();
}

// ── Verify with Flutterwave ────────────────────────────────
$FLW_SECRET = "YOUR_FLUTTERWAVE_SECRET_KEY";

$ch = curl_init("https://api.flutterwave.com/v3/transactions/{$transaction_id}/verify");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$FLW_SECRET}",
        "Content-Type: application/json",
    ],
]);
$res  = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);

if ($info["http_code"] !== 200) {
    http_response_code(502);
    echo json_encode(["status" => "error", "message" => "Could not reach Flutterwave."]);
    exit();
}

$flw = json_decode($res, true);
$txData = $flw["data"] ?? null;

if (
    !$txData ||
    $flw["status"] !== "success" ||
    $txData["status"] !== "successful" ||
    $txData["tx_ref"] !== $tx_ref ||
    strtolower($txData["customer"]["email"]) !== strtolower($email) ||
    (int) $txData["amount"] < $expectedPrice ||
    strtoupper($txData["currency"]) !== "NGN"
) {
    http_response_code(402);
    echo json_encode(["status" => "error", "message" => "Payment verification failed."]);
    exit();
}

// ── Credit the user ────────────────────────────────────────
$pdo->beginTransaction();

try {
    // Add credits to subscription
    $update = $pdo->prepare("
        UPDATE subscriptions
        SET zara_credits = zara_credits + :credits
        WHERE LOWER(email) = LOWER(:email)
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $update->execute([":credits" => $expectedCredits, ":email" => $email]);

    // Log the transaction
    $log = $pdo->prepare("
        INSERT INTO zara_topup_logs
            (email, tx_ref, transaction_id, pack_id, credits, amount, created_at)
        VALUES
            (:email, :tx_ref, :transaction_id, :pack_id, :credits, :amount, NOW())
    ");
    $log->execute([
        ":email"          => $email,
        ":tx_ref"         => $tx_ref,
        ":transaction_id" => $transaction_id,
        ":pack_id"        => $pack_id,
        ":credits"        => $expectedCredits,
        ":amount"         => $txData["amount"],
    ]);

    $pdo->commit();

    echo json_encode([
        "status"  => "success",
        "message" => "Credits added.",
        "credits" => $expectedCredits,
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "DB error: " . $e->getMessage()]);
}

