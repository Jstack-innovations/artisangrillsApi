<?php
require_once __DIR__ . "/../../SECURE/authGuard.php";


$file = __DIR__ . '/../../SECURE/db.php';

if (!file_exists($file)) {
    die(json_encode(["error" => "db.php not found"]));
}

require_once $file;


$admins = $conn->query("SELECT * FROM admins ORDER BY id DESC");
$result = [];
while($a = $admins->fetch_assoc()) {
    $result[] = $a;
}

// Wrap in "admins" key to match React
echo json_encode(["admins" => $result]);
?>
