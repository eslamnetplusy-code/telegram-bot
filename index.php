<?php
http_response_code(200);
session_start();

// ================= CONFIG =================
$botToken = "8057785864:AAG-TggKI7ILG7JLSEwAuwz6F5WH7ddTne0";

$apiUrl   = "https://megatec-center.com/api/request";
$apiToken = "fpl08cFMtJKHk5niYZuqd9r6LyBV2QDCNmwWv1UeRXIxo";

// ================= READ UPDATE =================
$raw = file_get_contents("php://input");
$update = json_decode($raw, true);

if (!isset($update["message"])) {
    exit;
}

$message = $update["message"];
$chat_id = $message["chat"]["id"];
$text    = trim($message["text"] ?? "");

// ================= SEND MESSAGE =================
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

// ================= LOGIC =================

// /start
if ($text === "/start") {

    $_SESSION["step"] = null;
    $_SESSION["service"] = null;

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

// اختيار الباقة
if ($text === "🎮 10 شدّات") {
    $_SESSION["service"] = "1114";
    $_SESSION["step"] = "player_id";
    sendMessage($chat_id, "✍️ أرسل <b>Player ID</b> الآن:");
    exit;
}

if ($text === "🎮 60 شدّة") {
    $_SESSION["service"] = "1101";
    $_SESSION["step"] = "player_id";
    sendMessage($chat_id, "✍️ أرسل <b>Player ID</b> الآن:");
    exit;
}

// استقبال Player ID
if ($_SESSION["step"] === "player_id" && is_numeric($text)) {

    $service = $_SESSION["service"];
    $_SESSION = []; // تفريغ الجلسة

    $reference = time() . rand(100, 999);

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
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        sendMessage($chat_id, "❌ خطأ في الاتصال:\n$error");
        exit;
    }

    sendMessage(
        $chat_id,
        "✅ <b>تم إرسال طلب الشحن</b>\n\n📄 رد النظام:\n<pre>$response</pre>"
    );
    exit;
}

// أي شيء آخر
sendMessage($chat_id, "ℹ️ أرسل /start للبدء");
