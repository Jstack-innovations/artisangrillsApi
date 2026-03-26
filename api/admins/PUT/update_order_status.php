<?php
require_once __DIR__ . "/../../SECURE/authGuard.php";

require_once __DIR__ . '/../../SECURE/config.php';


if ($_SERVER['REQUEST_METHOD'] === 'PUT') {

    $plate = $_POST['plate'] ?? '';
    $status = $_POST['status'] ?? '';

    $stmt = $pdo->prepare("
        UPDATE paid_orders 
        SET order_status = ? 
        WHERE plate_order_no = ?
    ");

    $stmt->execute([$status, $plate]);

    echo json_encode(['success' => true]);
}
