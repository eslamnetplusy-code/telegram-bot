<?php

// ================= CONFIG =================
$botToken = "8057785864:AAG-TggKI7ILG7JLSEwAuwz6F5WH7ddTne0";

$apiUrl   = "https://megatec-center.com/api/request";
$apiUser  = "u_3862970154";
$apiToken = "fpl08cFMtJKHk5niYZuqd9r6LyBV2QDCNmwWv1UeRXIxo";

// ================= READ UPDATE =================
$update = json_decode(file_get_contents("php://input"), true);
if (!isset($update["message"])) exit;

$chat_id = $update["message"]["chat"]["id"];
$text    = trim($update["message"]["text"] ?? "");

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

// ================= START =================
if ($text === "/start") {
    sendMessage(
        $chat_id,
        "✅ <b>أهلاً بك</b>\nاختر الباقة:",
        [
            "keyboard" => [
                ["🔹 10 شدّات"],
                ["🔹 60 شدّة"]
            ],
            "resize_keyboard" => true
        ]
    );
    exit;
}

// ================= اختيار الباقة =================
$service = null;

if ($text === "🔹 10 شدّات") {
    $service = 1114;
} elseif ($text === "🔹 60 شدّة") {
    $service = 1101;
}

if ($service) {
    file_put_contents("order_$chat_id.txt", $service);
    sendMessage($chat_id, "✍️ أرسل الآن <b>Player ID</b>");
    exit;
}

// ================= استقبال Player ID =================
if (is_numeric($text) && file_exists("order_$chat_id.txt")) {

    $service = file_get_contents("order_$chat_id.txt");
    unlink("order_$chat_id.txt");

    $reference = time() . rand(100,999);

    // ========== CURL REQUEST ==========
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
    $error    = curl_error($ch);
    curl_close($ch);

    if ($error) {
        sendMessage($chat_id, "❌ خطأ اتصال:\n$error");
        exit;
    }

    sendMessage(
        $chat_id,
        "✅ <b>تم إرسال الطلب بنجاح</b>\n\n<pre>$response</pre>"
    );
    exit;
}

// ================= DEFAULT =================
sendMessage($chat_id, "❗️الرجاء إرسال /start للبدء");
