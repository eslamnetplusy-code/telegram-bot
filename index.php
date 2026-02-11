<?php
http_response_code(200);

// ================= CONFIG =================
$botToken = "8057785864:AAG-TggKI7ILG7JLSEwAuwz6F5WH7ddTne0"; // ضع التوكن الجديد هنا

$apiUrl   = "https://megatec-center.com/api/request";
$apiUser  = "u_3862970154";
$apiToken = "fpl08cFMtJKHk5niYZuqd9r6LyBV2QDCNmwWv1UeRXIxo";

// ================= READ UPDATE =================
$update = json_decode(file_get_contents("php://input"), true);

if (!isset($update["message"])) {
    exit;
}

$message = $update["message"];
$chat_id = $message["chat"]["id"];
$text    = trim($message["text"] ?? "");

// ================= SEND MESSAGE (FIXED) =================
function sendMessage($chat_id, $text, $keyboard = null) {
    global $botToken;

    $url = "https://api.telegram.org/bot$botToken/sendMessage";

    $data = [
        "chat_id" => $chat_id,
        "text" => $text,
        "parse_mode" => "HTML"
    ];

    if ($keyboard) {
        $data["reply_markup"] = json_encode($keyboard, JSON_UNESCAPED_UNICODE);
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => $data
    ]);

    curl_exec($ch);
    curl_close($ch);
}

// ================= START =================
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

// ================= اختيار الباقة =================
if ($text === "🎮 10 شدّات") {
    file_put_contents("order_$chat_id.txt", "1114");
    sendMessage($chat_id, "✍️ أرسل <b>Player ID</b> الآن:");
    exit;
}

if ($text === "🎮 60 شدّة") {
    file_put_contents("order_$chat_id.txt", "1101");
    sendMessage($chat_id, "✍️ أرسل <b>Player ID</b> الآن:");
    exit;
}

// ================= استقبال Player ID =================
if (is_numeric($text) && file_exists("order_$chat_id.txt")) {

    $service = file_get_contents("order_$chat_id.txt");
    unlink("order_$chat_id.txt");

    $reference = time() . rand(100,999);

    $ch = curl_init($apiUrl);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $apiToken"
        ],
        CURLOPT_POSTFIELDS => [
            "request"   => "neworder",
            "service"   => $service,
            "reference" => $reference,
            "player_id" => $text
        ]
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        sendMessage($chat_id, "❌ خطأ في الاتصال:\n" . curl_error($ch));
        curl_close($ch);
        exit;
    }

    curl_close($ch);

    sendMessage(
        $chat_id,
        "✅ <b>تم إرسال طلب الشحن</b>\n\n📄 رد النظام:\n<pre>$response</pre>"
    );
    exit;
}

// ================= افتراضي =================
sendMessage($chat_id, "ℹ️ أرسل /start للبدء");
