<?php
require_once __DIR__ . "/../../SECURE/db.php";

// Set timezone
date_default_timezone_set("Africa/Lagos");

// Initialize last 7 days
$dailyRevenue = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dailyRevenue[$date] = 0;
}

// Fetch revenue from DB
$sql = "
SELECT 
    DATE(created_at) as order_date,
    SUM(total_amount) as total
FROM paid_orders
WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
GROUP BY DATE(created_at)
ORDER BY DATE(created_at) ASC
";

$res = $conn->query($sql);

while ($row = $res->fetch_assoc()) {
    $dailyRevenue[$row['order_date']] = floatval($row['total']);
}

// Format for frontend
$output = ["dailyRevenue" => []];
foreach ($dailyRevenue as $date => $total) {
    $dayName = date('D', strtotime($date)); // Mon, Tue, etc
    $output["dailyRevenue"][] = [
        "day" => $dayName,
        "revenue" => $total
    ];
}

// Return JSON
header('Content-Type: application/json');
echo json_encode($output);
