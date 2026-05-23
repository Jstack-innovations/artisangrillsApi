<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
header("Content-Type: application/json");

// Read JSON file
$jsonPath = __DIR__ . "/../GET/JSON/countdown.json";

if (!file_exists($jsonPath)) {
    die(json_encode([
        "status" => "error",
        "message" => "countdown.json not found",
        "path_checked" => $jsonPath
    ]));
}

$data = json_decode(file_get_contents($jsonPath), true);


$days = $data["free_trial_days"];
$startDate = $data["start_date"];

// Convert to timestamps
$startTimestamp = strtotime($startDate);
$endTimestamp = strtotime("+$days days", $startTimestamp);

$currentTimestamp = time();

$remainingSeconds = $endTimestamp - $currentTimestamp;

if ($remainingSeconds <= 0) {
    echo json_encode([
        "status" => "expired",
        "remaining_days" => 0,
        "remaining_hours" => 0,
        "remaining_minutes" => 0
    ]);
    exit;
}

$remainingDays = floor($remainingSeconds / 86400);
$remainingHours = floor(($remainingSeconds % 86400) / 3600);
$remainingMinutes = floor(($remainingSeconds % 3600) / 60);

echo json_encode([
    "status" => "active",
    "remaining_days" => $remainingDays,
    "remaining_hours" => $remainingHours,
    "remaining_minutes" => $remainingMinutes
]);

?>
