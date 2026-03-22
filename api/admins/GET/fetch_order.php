<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json");

require_once __DIR__ . '/../../SECURE/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $plate = $_POST['tracking_number'] ?? '';

    $stmt = $pdo->prepare("
        SELECT name, phone, total_amount, order_status 
        FROM paid_orders 
        WHERE plate_order_no = ?
    ");

    $stmt->execute([$plate]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode($order ?: []);
}
