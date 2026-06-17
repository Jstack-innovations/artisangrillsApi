<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Subscription-Code");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") { http_response_code(200); exit(); }

require_once __DIR__ . "/../../SECURE/db.php";

// --- Verify subscription code ---
$sub_code = $_SERVER["HTTP_X_SUBSCRIPTION_CODE"] ?? "";
if (empty($sub_code)) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}

// --- Revenue today ---
$sqlRevenue = "SELECT SUM(total_amount) as total FROM paid_orders WHERE DATE(created_at) = CURDATE()";
$resRevenue = $conn->query($sqlRevenue);
$revenueToday = floatval($resRevenue->fetch_assoc()['total'] ?? 0);

// --- Orders today ---
$sqlOrders = "SELECT COUNT(*) as cnt FROM paid_orders WHERE DATE(created_at) = CURDATE()";
$resOrders = $conn->query($sqlOrders);
$ordersToday = intval($resOrders->fetch_assoc()['cnt'] ?? 0);

// --- Tables seated ---
$sqlSeated = "SELECT COUNT(*) as cnt FROM tables WHERE status = 'occupied'";
$resSeated = $conn->query($sqlSeated);
$tablesSeated = intval($resSeated->fetch_assoc()['cnt'] ?? 0);

$sqlTotal = "SELECT COUNT(*) as cnt FROM tables";
$resTotal = $conn->query($sqlTotal);
$tablesTotal = intval($resTotal->fetch_assoc()['cnt'] ?? 0);

// --- Output ---
echo json_encode([
    "stats" => [
        "revenue_today"  => $revenueToday,
        "orders_today"   => $ordersToday,
        "tables_seated"  => $tablesSeated,
        "tables_total"   => $tablesTotal,
        "zara_chats_today" => 0
    ]
]);
