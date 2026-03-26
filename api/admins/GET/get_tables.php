<?php
require_once __DIR__ . "/../../SECURE/authGuard.php";

$file = __DIR__ . '/../../SECURE/db.php';

if (!file_exists($file)) {
    die(json_encode(["error" => "db.php not found"]));
}

require_once $file;

/* Load tables JSON */
$tablesFile = __DIR__ . "/../../GET/JSON/tables.json";

if (!file_exists($tablesFile)) {
    die(json_encode(["error" => "tables.json not found"]));
}

$tablesJson = json_decode(file_get_contents($tablesFile), true);
$floors = $tablesJson["floors"] ?? [];

/* Fetch booked tables */
$res = $conn->query("SELECT * FROM booked_tables");

$bookedRows = [];
while ($row = $res->fetch_assoc()) {
    $bookedRows[$row["table_id"]] = $row;
}

/* Build FINAL response grouped by floors */
$final = [
    "floors" => []
];

foreach ($floors as $floorName => $tables) {

    $final["floors"][$floorName] = [];

    foreach ($tables as $table) {

        $id = $table["id"];

        $booking = $bookedRows[$id] ?? null;

        $final["floors"][$floorName][] = [
            "id" => $table["id"],
            "number" => $table["number"],
            "seats" => $table["seats"],
            "amount" => $table["amount"],
            "image" => $table["image"],
            "description" => $table["description"],

            // 🔥 booking status added from DB
            "booked" => isset($booking["booked"]) ? (int)$booking["booked"] : 0,
            "booked_id" => $booking["id"] ?? null
        ];
    }
}

/* RETURN EXACT STRUCTURE */
echo json_encode($final);
