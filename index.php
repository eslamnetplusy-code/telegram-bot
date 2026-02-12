<?php
http_response_code(200);
set_time_limit(0);

$botToken = "8057785864:AAG-TggKI7ILG7JLSEwAuwz6F5WH7ddTne0";

$apiUrl  = "https://megatec-center.com/api/rest.php";
$apiKey  = "fpl08cFMtJKHk5niYZuqd9r6LyBV2QDCNmwWv1UeRXIxo"; // 🔥 ضع التوكن هنا

$update = json_decode(file_get_contents("php://input"), true);
if (!$update) exit;

$message = $update["message"] ?? null;
if (!$message) exit;

$chat_id = $message["chat"]["id"];
$text    = trim($message["text"] ?? "");

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

    file_get_contents(
        "https://api.telegram.org/bot$botToken/sendMessage?" .
        http_build_query($data)
    );
}

// ================= SEND ORDER =================

function sendOrder($service_id, $player_id) {
    global $apiUrl, $apiKey;

    $postData = [
        "request"   => "neworder",
        "service"   => $service_id,
        "player_id" => $player_id,
        "key"       => $apiKey
    ];

    $ch = curl_init($apiUrl);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        return [
            "status" => false,
            "message" => curl_error($ch)
        ];
    }

    curl_close($ch);

    return json_decode($response, true);
}

// ================= BOT LOGIC =================

if ($text === "/start") {
    sendMessage(
        $chat_id,
        "👋 مرحباً بك\n\nاختر باقة شحن:",
        [
            "keyboard" => [
                ["🎮 60 شدّة"]
            ],
            "resize_keyboard" => true
        ]
    );
    exit;
}

if ($text === "🎮 60 شدّة") {
    file_put_contents("order_$chat_id.txt", "1101");
    sendMessage($chat_id, "✍️ أرسل Player ID الآن:");
    exit;
}

if (is_numeric($text) && file_exists("order_$chat_id.txt")) {

    $service = file_get_contents("order_$chat_id.txt");
    unlink("order_$chat_id.txt");

    sendMessage($chat_id, "⏳ جاري التنفيذ...");

    $result = sendOrder($service, $text);

    if (isset($result["status"]) && $result["status"] == true) {

        sendMessage(
            $chat_id,
            "✅ تم التنفيذ بنجاح\n\nرقم الطلب:\n" .
            ($result["order"] ?? "غير معروف")
        );

    } else {

        sendMessage(
            $chat_id,
            "❌ فشل تنفيذ الطلب\n\nالسبب:\n" .
            ($result["message"] ?? "غير معروف")
        );
    }

    exit;
}

sendMessage($chat_id, "أرسل /start للبدء");
