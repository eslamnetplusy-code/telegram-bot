<?php
http_response_code(200);
set_time_limit(0);

// ================== CONFIG ==================
$botToken = "8057785864:AAG-TggKI7ILG7JLSEwAuwz6F5WH7ddTne0";

$apiUsername = "u_3862970154";
$apiKey      = "http://185.112.200.88/yemen_robot";

$apiUrl = "https://megatec-center.com/api/rest/$apiUsername/$apiKey";

// ================== GET UPDATE ==================
$update = json_decode(file_get_contents("php://input"), true);

if (!isset($update["message"])) {
    exit;
}

$message = $update["message"];
$chat_id = $message["chat"]["id"];
$text    = trim($message["text"] ?? "");

// ================== SEND MESSAGE ==================
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

    file_get_contents(
        "https://api.telegram.org/bot$botToken/sendMessage?" .
        http_build_query($data)
    );
}

// ================== API ORDER FUNCTION ==================
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
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        return [
            "status" => false,
            "message" => curl_error($ch)
        ];
    }

    curl_close($ch);

    return [
        "raw" => $response
    ];
}

// ================== BOT LOGIC ==================

if ($text == "/start") {

    sendMessage(
        $chat_id,
        "👋 <b>مرحباً بك</b>\n\nاختر باقة شحن شدّات ببجي:",
        [
            "keyboard" => [
                ["🎮 10 شدّات"],
                ["🎮 60 شدّة"]
            ],
            "resize_keyboard" => true
        ]
    );
    exit;
}

// اختيار 10 شدات
if ($text == "🎮 10 شدّات") {
    file_put_contents("order_$chat_id.txt", "1114");
    sendMessage($chat_id, "✍️ أرسل <b>Player ID</b> الآن:");
    exit;
}

// اختيار 60 شدات
if ($text == "🎮 60 شدّة") {
    file_put_contents("order_$chat_id.txt", "1101");
    sendMessage($chat_id, "✍️ أرسل <b>Player ID</b> الآن:");
    exit;
}

// استقبال Player ID
if (is_numeric($text) && file_exists("order_$chat_id.txt")) {

    $service = file_get_contents("order_$chat_id.txt");
    unlink("order_$chat_id.txt");

    $result = sendOrder($service, $text);

    sendMessage(
        $chat_id,
        "🔍 رد السيرفر:\n\n<pre>" . print_r($result, true) . "</pre>"
    );
    exit;
}

// أي رسالة أخرى
sendMessage($chat_id, "ℹ️ للبدء أرسل /start");
