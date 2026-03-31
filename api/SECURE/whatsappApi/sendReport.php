<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require __DIR__ . '/vendor/autoload.php';
use Twilio\Rest\Client;

// ===== Twilio Credentials =====
$sid = "ACc9ad391bff43d4ce8463574671abbe5";
$token = "9eed46d720ded0519127b7e28dcf38ea";
$fromWhatsapp = "whatsapp:+14155238886";
$toWhatsapp = "whatsapp:+2347089913116";

$client = new Client($sid, $token);

// ===== DB Connection =====
$file = __DIR__ . '/../SECURE/db.php';
if (!file_exists($file)) die(json_encode(["error" => "db.php not found"]));
require_once $file; // should provide $conn (PDO or mysqli)

// ===== Parameters =====
$costPercentage = 0.65; // same as your dashboard

try {
    // Fetch today’s paid orders
    $stmt = $conn->prepare("
        SELECT SUM(total_amount) AS grossRevenue
        FROM paid_orders
        WHERE status = 'paid' AND DATE(created_at) = CURDATE()
    ");
    $stmt->execute();
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    $grossRevenue = $data['grossRevenue'] ?? 0;
    $estimatedCost = $grossRevenue * $costPercentage;
    $estimatedProfit = $grossRevenue - $estimatedCost;
    $profitMargin = $grossRevenue ? round(($estimatedProfit / $grossRevenue) * 100) : 0;

    // Build message
    $message = "📊 Artisanè Grilluxxè Daily Business Report\n\n";
    $message .= "💰 Gross Revenue: ₦" . number_format($grossRevenue) . "\n";
    $message .= "📉 Estimated Cost: ₦" . number_format($estimatedCost) . "\n";
    $message .= "📈 Estimated Profit: ₦" . number_format($estimatedProfit) . "\n";
    $message .= "📊 Profit Margin: {$profitMargin}%\n\n";
    $message .= "Generated automatically.";

    // Send WhatsApp
    $client->messages->create(
        $toWhatsapp,
        [
            "from" => $fromWhatsapp,
            "body" => $message
        ]
    );

    echo "Report sent successfully!";

} catch (PDOException $e) {
    echo "DB Error: " . $e->getMessage();
} catch (Exception $e) {
    echo "Twilio Error: " . $e->getMessage();
}
