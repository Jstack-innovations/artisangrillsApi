<?php
require_once __DIR__ . "/../../SECURE/authGuard.php";

$file = __DIR__ . '/../../SECURE/db.php';

if (!file_exists($file)) {
    die(json_encode(["error" => "db.php not found"]));
}

require_once $file;

/* Load menu JSON */
$menuFile = __DIR__ . "/../../GET/JSON/menu.json";
$menuJson = [];

if (file_exists($menuFile)) {
    $menuJson = json_decode(file_get_contents($menuFile), true) ?? [];
}

/* Build image map */
$menuImages = [];
foreach ($menuJson as $category) {
    foreach ($category as $m) {
        $menuImages[$m['id']] = $m['image'];
    }
}

$id = $_GET['id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(["error" => "Missing order id"]);
    exit;
}

/* =========================
   GET SINGLE ORDER + ITEMS
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    // Fetch order info
    $order = $conn->query("SELECT * FROM paid_orders WHERE id='$id'")
                  ->fetch_assoc();

    if (!$order) {
        http_response_code(404);
        echo json_encode(["error" => "Order not found"]);
        exit;
    }

    // Fetch order items
    $itemsRes = $conn->query("
        SELECT * FROM paid_order_items 
        WHERE paid_order_id = '$id'
    ");

    $items = [];

    while ($row = $itemsRes->fetch_assoc()) {
        $items[] = [
            "name" => $row['menu_name'],
            "price" => $row['price'],
            "qty" => $row['quantity'],
            "image" => $menuImages[$row['menu_id']] ?? "images/default.jpg",
            "menu_id" => $row['menu_id'],
            "order_item_id" => $row['id']
        ];
    }

    echo json_encode([
        "info" => $order,
        "items" => $items
    ]);

    exit;
}

/* =========================
   UPDATE ORDER
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {

    $data = json_decode(file_get_contents("php://input"), true);

    $stmt = $conn->prepare("UPDATE paid_orders SET 
        name=?, phone=?, table_no=?, order_type=?, total_amount=?, 
        payment_ref=?, order_status=?, full_address=?, plate_order_no=? 
        WHERE id=?");

    $stmt->bind_param(
        "sssssssssi",
        $data['name'],
        $data['phone'],
        $data['table_no'],
        $data['order_type'],
        $data['total_amount'],
        $data['payment_ref'],
        $data['order_status'],
        $data['full_address'],
        $data['plate_order_no'],
        $id
    );

    $stmt->execute();

    echo json_encode(["success" => true]);
}
