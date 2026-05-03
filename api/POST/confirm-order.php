<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../SECURE/db.php';
require_once __DIR__ . '/../SECURE/gmailApi/resend_mailer.php';

$data = json_decode(file_get_contents("php://input"), true);

$order_id       = $data['order_id'] ?? null;
$transaction_id = $data['transaction_id'] ?? '';
$orderType      = $data['order_type'] ?? 'table';
$tableNo        = $data['table_no'] ?? '';
$cart           = $data['cart'] ?? [];

if (!$order_id || !$transaction_id || empty($cart)) {
    echo json_encode(["status" => "error", "message" => "Missing data"]);
    exit;
}

/* ===== GET SECRET KEY ===== */
ob_start();
include __DIR__ . '/../SECURE/flutterwave-key.php';
$keyOutput = ob_get_clean();

$keyData = json_decode($keyOutput, true);
$secretKey = $keyData['secretKey'] ?? '';

if (!$secretKey) {
    echo json_encode(["status" => "error", "message" => "Secret key not found"]);
    exit;
}

/* ===== VERIFY PAYMENT ===== */
$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.flutterwave.com/v3/transactions/$transaction_id/verify",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $secretKey"
    ],
]);

$response = curl_exec($curl);

if (curl_errno($curl)) {
    echo json_encode(["status" => "error", "message" => "Payment gateway error"]);
    exit;
}

curl_close($curl);

$result = json_decode($response, true);

if (
    !$result ||
    ($result['status'] ?? '') !== 'success' ||
    ($result['data']['status'] ?? '') !== 'successful'
) {
    echo json_encode(["status" => "error", "message" => "Payment not verified"]);
    exit;
}

/* ===== GET DB AMOUNT (SOURCE OF TRUTH) ===== */
$stmt = $conn->prepare("SELECT total_amount, status FROM paid_orders WHERE id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$stmt->bind_result($db_amount, $status);
$stmt->fetch();
$stmt->close();

/* ===== ALREADY PAID CHECK ===== */
if ($status === 'paid') {
    echo json_encode(["status" => "error", "message" => "Already paid"]);
    exit;
}

/* ===== AMOUNT CHECK ===== */
$flutter_amount = (float) $result['data']['amount'];
$db_amount = (float) $db_amount;

/* allow tiny rounding differences */
if (abs($db_amount - $flutter_amount) > 0.01) {
    echo json_encode([
        "status" => "error",
        "message" => "Amount mismatch",
        "db" => $db_amount,
        "flutterwave" => $flutter_amount
    ]);
    exit;
}

/* ===== PAYMENT REF ===== */
$payment_ref = $transaction_id;

/* ===== TRANSACTION ===== */
$conn->begin_transaction();

try {

    /* UPDATE ORDER */
    $stmt = $conn->prepare("
        UPDATE paid_orders
        SET status = 'paid', payment_ref = ?
        WHERE id = ? AND status = 'payment_pending'
    ");

    $stmt->bind_param("si", $payment_ref, $order_id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception("Order update failed");
    }

    /* TABLE BOOKING */
    if ($orderType === 'table' && !empty($tableNo)) {

        $stmt2 = $conn->prepare("
            INSERT INTO booked_tables (table_id, booked)
            VALUES (?, 1)
            ON DUPLICATE KEY UPDATE booked = 1
        ");

        $stmt2->bind_param("i", $tableNo);
        $stmt2->execute();
    }

    /* STOCK UPDATE */
    $stockStmt = $conn->prepare("
        UPDATE menu_stock 
        SET stock = stock - ?, 
            available = CASE WHEN stock - ? <= 0 THEN 0 ELSE 1 END
        WHERE menu_id = ? AND stock >= ?
    ");

    foreach ($cart as $item) {

        $qty = (int)$item['quantity'];
        $id  = (int)$item['id'];

        $stockStmt->bind_param("iiii", $qty, $qty, $id, $qty);
        $stockStmt->execute();

        if ($stockStmt->affected_rows === 0) {
            throw new Exception("Stock error: " . $item['name']);
        }
    }

    $conn->commit();

    echo json_encode([
        "status" => "success",
        "order_id" => $order_id
    ]);

} catch (Exception $e) {
    $conn->rollback();

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
