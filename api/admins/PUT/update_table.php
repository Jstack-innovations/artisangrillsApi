<?php
session_start();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

$file = __DIR__ . '/../../SECURE/db.php';

if (!file_exists($file)) {
    die(json_encode(["success" => false, "error" => "db.php not found"]));
}

require_once $file;

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Read JSON body
$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id'] ?? null;
$action = $data['action'] ?? null;
$booked = $data['booked'] ?? 0;
$booked_id = $data['booked_id'] ?? null;

if (!$id || !$action) {
    echo json_encode(["success" => false, "error" => "Missing parameters"]);
    exit;
}

//
// ✅ 1. EDIT → UPDATE JSON FILE
//
if ($action === "edit") {

    $tablesFile = __DIR__ . "/../../GET/JSON/tables.json";

    if (!file_exists($tablesFile)) {
        echo json_encode(["success" => false, "error" => "tables.json not found"]);
        exit;
    }

    $json = json_decode(file_get_contents($tablesFile), true);

    if (!isset($json["floors"])) {
        echo json_encode(["success" => false, "error" => "Invalid JSON structure"]);
        exit;
    }

    // Loop through floors and update matching table
    foreach ($json["floors"] as $floorName => &$tables) {
        foreach ($tables as &$table) {
            if ($table["id"] == $id) {

                $table["number"] = $data["number"] ?? $table["number"];
                $table["seats"] = $data["seats"] ?? $table["seats"];
                $table["description"] = $data["description"] ?? $table["description"];
                $table["image"] = $data["image"] ?? $table["image"];
                $table["amount"] = $data["amount"] ?? $table["amount"];

                break 2; // stop both loops
            }
        }
    }

    // Save back to JSON
    file_put_contents($tablesFile, json_encode($json, JSON_PRETTY_PRINT));

    echo json_encode(["success" => true]);
    exit;
}

//
// ✅ 2. BOOKING UPDATE → DATABASE
//
if ($action === "update") {

    if ($booked_id) {
        $stmt = $conn->prepare("UPDATE booked_tables SET booked=? WHERE id=?");
        $stmt->bind_param("ii", $booked, $booked_id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("INSERT INTO booked_tables (table_id, booked) VALUES (?, ?)");
        $stmt->bind_param("ii", $id, $booked);
        $stmt->execute();
    }

    echo json_encode(["success" => true]);
    exit;
}

//
// ✅ 3. DELETE → DATABASE
//
if ($action === "delete") {

    if ($booked_id) {
        $stmt = $conn->prepare("DELETE FROM booked_tables WHERE id=?");
        $stmt->bind_param("i", $booked_id);
        $stmt->execute();

        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => "Nothing to delete"]);
    }

    exit;
}

//
// ❌ UNKNOWN ACTION
//
echo json_encode(["success" => false, "error" => "Invalid action"]);
