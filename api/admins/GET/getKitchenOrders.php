<?php
require_once __DIR__ . "/../../SECURE/authGuard.php";
require_once __DIR__ . "/../../SECURE/db.php";

$result = $conn->query("
    SELECT * FROM kitchen_production 
    WHERE status = 'pending' 
    ORDER BY created_at ASC
");

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

echo json_encode(["orders" => $orders]);
