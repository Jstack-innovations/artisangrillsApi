<?php

require_once __DIR__ . "/../../SECURE/authGuard.php";

$menuFile = __DIR__ . "/../../GET/JSON/menu.json";

$menuJson = json_decode(file_get_contents($menuFile), true);

echo json_encode([
    "menu" => $menuJson
]);
