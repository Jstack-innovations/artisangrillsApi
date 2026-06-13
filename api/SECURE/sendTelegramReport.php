<?php
require_once __DIR__ . "/authGuard.php";

header("Content-Type: application/json");

$botToken = getenv("TELEGRAM_BOT_TOKEN");
$chatId   = getenv("TELEGRAM_CHAT_ID");

$file = __DIR__ . '/db.php';
if (!file_exists($file)) die(json_encode(["error" => "db.php not found"]));
require_once $file;

$costPercentage = 0.65;

try {
    // ===== Today's Revenue =====
    $stmt = $conn->prepare("
        SELECT SUM(total_amount) FROM paid_orders
        WHERE status = 'paid' AND DATE(created_at) = CURDATE()
    ");
    $stmt->execute();
    $stmt->bind_result($todayRevenue);
    $stmt->fetch();
    $stmt->close();
    $todayRevenue = $todayRevenue ?? 0;

    // ===== All-Time Revenue =====
    $stmt = $conn->prepare("
        SELECT SUM(total_amount) FROM paid_orders
        WHERE status = 'paid'
    ");
    $stmt->execute();
    $stmt->bind_result($allTimeRevenue);
    $stmt->fetch();
    $stmt->close();
    $allTimeRevenue = $allTimeRevenue ?? 0;

    // ===== Calculate both =====
    $todayCost      = $todayRevenue   * $costPercentage;
    $todayProfit    = $todayRevenue   - $todayCost;
    $todayMargin    = $todayRevenue   ? round(($todayProfit   / $todayRevenue)   * 100) : 0;

    $allTimeCost    = $allTimeRevenue * $costPercentage;
    $allTimeProfit  = $allTimeRevenue - $allTimeCost;
    $allTimeMargin  = $allTimeRevenue ? round(($allTimeProfit / $allTimeRevenue) * 100) : 0;

    // ===== Build Message =====
    $date = date("F j, Y");

    $message  = "📊 *Artisanè Grills Business Report*\n";
    $message .= "🗓 {$date}\n";
    $message .= "━━━━━━━━━━━━━━━━━━━━\n\n";

    $message .= "📅 *TODAY'S PERFORMANCE*\n";
    $message .= "💰 Gross Revenue: ₦" . number_format($todayRevenue) . "\n";
    $message .= "📉 Estimated Cost: ₦" . number_format($todayCost) . "\n";
    $message .= "📈 Estimated Profit: ₦" . number_format($todayProfit) . "\n";
    $message .= "📊 Profit Margin: {$todayMargin}%\n\n";

    $message .= "━━━━━━━━━━━━━━━━━━━━\n\n";

    $message .= "🏆 *ALL-TIME PERFORMANCE*\n";
    $message .= "💰 Gross Revenue: ₦" . number_format($allTimeRevenue) . "\n";
    $message .= "📉 Estimated Cost: ₦" . number_format($allTimeCost) . "\n";
    $message .= "📈 Estimated Profit: ₦" . number_format($allTimeProfit) . "\n";
    $message .= "📊 Profit Margin: {$allTimeMargin}%\n\n";

    $message .= "━━━━━━━━━━━━━━━━━━━━\n";
    $message .= "🤖 _Generated automatically by Enflow._";

    // ===== Send to Telegram =====
    $url     = "https://api.telegram.org/bot{$botToken}/sendMessage";
    $payload = http_build_query([
        "chat_id"    => $chatId,
        "text"       => $message,
        "parse_mode" => "Markdown"
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    if ($result["ok"]) {
        echo json_encode(["success" => true, "message" => "Report sent!"]);
    } else {
        echo json_encode(["success" => false, "message" => $result["description"]]);
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
