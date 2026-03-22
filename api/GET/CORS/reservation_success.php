<?php
// reservation_success.php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$file = __DIR__ . '/../../SECURE/db.php';

if (!file_exists($file)) {
    die(json_encode(["error" => "db.php not found"]));
}

require_once $file;

// Get reservation ID from query string
$reservation_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$reservation_id) {
    echo json_encode([
        "success" => false,
        "message" => "Reservation ID is required"
    ]);
    exit;
}

// Prepare query
$stmt = $conn->prepare("SELECT id, table_id, name, email, phone, booking_date, amount, transaction_id, reservation_code FROM reservations WHERE id = ?");
$stmt->bind_param("i", $reservation_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Reservation not found"
    ]);
    exit;
}

$reservation = $result->fetch_assoc();

// Convert table_id to integer and amount to float to match TS types
$reservation['tableId'] = (int)$reservation['table_id'];
$reservation['amount'] = (float)$reservation['amount'];
$reservation['bookingDate'] = $reservation['booking_date'];

unset($reservation['table_id']);
unset($reservation['booking_date']);

echo json_encode([
    "success" => true,
    "reservation" => $reservation
]);
