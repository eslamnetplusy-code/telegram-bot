<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

http_response_code(200);
set_time_limit(0);

$botToken = "8057785864:AAG-TggKI7ILG7JLSEwAuwz6F5WH7ddTne0";

$apiUser = "u_3862970154";
$apiPass = "Fekri-738911634";

$apiUrl = "https://megatec-center.com/api/rest.php";

// ================= SEND MESSAGE =================
function sendMessage($chat_id, $text, $keyboard = null) {
    global $botToken;

    $data = [
        "chat_id" => $chat_id,
        "text" => $text,
        "parse_mode" => "HTML"
    ];

    if ($keyboard) {
        $data["reply_markup"] = json_encode($keyboard);
    }

    $ch = curl_init("https://api.telegram.org/bot$botToken/sendMessage");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $data
    ]);
    curl_exec($ch);
    curl_close($ch);
}

// ================= SEND ORDER =================
function sendOrder($service_id, $player_id) {
    global $apiUrl, $apiUser, $apiPass;

    $reference = time() . rand(100,999);

    $postData = [
        "request"   => "neworder",
        "service"   => $service_id,
        "reference" => $reference,
        "player_id" => $player_id
    ];

    $ch = curl_init($apiUrl);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_USERPWD => "$apiUser:$apiPass",
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => false
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return "CURL ERROR: $error";
    }

    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return "HTTP CODE: $http\n\n$response";
}

// ================= READ UPDATE =================
$update = json_decode(file_get_contents("php://input"), true);

if (!isset($update["message"])) exit;

$chat_id = $update["message"]["chat"]["id"];
$text = trim($update["message"]["text"] ?? "");

// ================= BOT LOGIC =================
if ($text == "/start") {
    sendMessage($chat_id, "👋 مرحباً بك\nاختر الباقة:", [
        "keyboard" => [
            ["🎮 60 شدّة"]
        ],
        "resize_keyboard" => true
    ]);
    exit;
}

if ($text == "🎮 60 شدّة") {
    file_put_contents("order_$chat_id.txt", "1101");
    sendMessage($chat_id, "✍️ أرسل Player ID (يجب أن يبدأ بالرقم 5):");
    exit;
}

if (is_numeric($text) && file_exists("order_$chat_id.txt")) {

    if (substr($text, 0, 1) != "5") {
        sendMessage($chat_id, "❌ يجب أن يبدأ Player ID بالرقم 5");
        exit;
    }

    $service = file_get_contents("order_$chat_id.txt");
    unlink("order_$chat_id.txt");

    sendMessage($chat_id, "⏳ جاري تنفيذ الطلب...");

    $result = sendOrder($service, $text);

    sendMessage($chat_id, "🔍 نتيجة السيرفر:\n\n<pre>$result</pre>");
    exit;
}

sendMessage($chat_id, "أرسل /start للبدء");
