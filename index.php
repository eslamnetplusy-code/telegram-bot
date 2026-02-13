<?php
http_response_code(200);
ignore_user_abort(true);
set_time_limit(0);

// ========= CONFIG =========
$botToken = "8057785864:AAG-TggKI7ILG7JLSEwAuwz6F5WH7ddTne0";
$apiUsername = "u_3862970154";
$apiKey = "http://185.112.200.88/yemen_robot";

$apiUrl = "https://megatec-center.com/api/rest/$apiUsername/$apiKey";

// ========= GET UPDATE =========
$update = json_decode(file_get_contents("php://input"), true);
if (!isset($update["message"])) exit;

$message = $update["message"];
$chat_id = $message["chat"]["id"];
$text = trim($message["text"] ?? "");

// ========= SEND MESSAGE =========
function sendMessage($chat_id, $text, $keyboard = null) {
    global $botToken;

    $data = [
        "chat_id" => $chat_id,
        "text" => $text
    ];

    if ($keyboard) {
        $data["reply_markup"] = json_encode($keyboard);
    }

    $ch = curl_init("https://api.telegram.org/bot$botToken/sendMessage");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_TIMEOUT => 10
    ]);
    curl_exec($ch);
    curl_close($ch);
}

// ========= SEND ORDER =========
function sendOrder($service_id, $player_id) {
    global $apiUrl;

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
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return "CURL ERROR: $error";
    }

    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return "HTTP CODE: $httpcode\n\n$response";
}

// ========= BOT LOGIC =========

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
    sendMessage($chat_id, "✍️ أرسل Player ID:");
    exit;
}

if (is_numeric($text) && file_exists("order_$chat_id.txt")) {

    $service = file_get_contents("order_$chat_id.txt");
    unlink("order_$chat_id.txt");

    sendMessage($chat_id, "⏳ جاري تنفيذ الطلب...");

    $result = sendOrder($service, $text);

    sendMessage($chat_id, "🔍 نتيجة السيرفر:\n\n$result");
    exit;
}

sendMessage($chat_id, "أرسل /start للبدء");
