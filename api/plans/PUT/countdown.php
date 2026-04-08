<?php

// Read JSON file
$data = json_decode(file_get_contents("plan.json"), true);

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
