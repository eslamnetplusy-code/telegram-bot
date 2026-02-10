<?php
http_response_code(200);

$botToken = getenv("8057785864:AAG-TggKI7ILG7JLSEwAuwz6F5WH7ddTne0"); // أو ضع التوكن مباشرة مؤقتًا

$update = json_decode(file_get_contents("php://input"), true);

if (!isset($update["message"])) {
    exit;
}

$message = $update["message"];
$chat_id = $message["chat"]["id"];
$text    = trim($message["text"] ?? "");

// ====== دالة إرسال ======
function sendMessage($chat_id, $text, $keyboard = null) {
    global $botToken;

    $data = [
        "chat_id" => $chat_id,
        "text" => $text,
        "parse_mode" => "HTML"
    ];

    if ($keyboard) {
        $data["reply_markup"] = json_encode($keyboard, JSON_UNESCAPED_UNICODE);
    }

    $ch = curl_init("https://api.telegram.org/bot$botToken/sendMessage");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => $data
    ]);
    curl_exec($ch);
    curl_close($ch);
}

// ====== استقبال /start (الطريقة الصحيحة) ======
if (strpos($text, "/start") === 0) {

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

// ====== رد افتراضي ======
sendMessage($chat_id, "ℹ️ أرسل /start للبدء");
