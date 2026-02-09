<?php

// ====== إعدادات من Railway Variables ======
$botToken = getenv("BOT_TOKEN");
$apiUrl   = getenv("API_URL");
$apiUser  = getenv("API_USER");
$apiToken = getenv("API_TOKEN");

// ====== استقبال التحديث من تيليجرام ======
$update = json_decode(file_get_contents("php://input"), true);
$message = $update["message"] ?? null;

if (!$message) {
    exit;
}

$chat_id = $message["chat"]["id"];
$text    = trim($message["text"] ?? "");

// ====== دالة إرسال رسالة (آمنة + تدعم العربي) ======
function sendMessage($chat_id, $text) {
    global $botToken;

    $url = "https://api.telegram.org/bot$botToken/sendMessage";

    $data = [
        "chat_id" => $chat_id,
        "text"    => $text
    ];

    $options = [
        "http" => [
            "header"  => "Content-Type: application/json",
            "method"  => "POST",
            "content" => json_encode($data, JSON_UNESCAPED_UNICODE),
        ]
    ];

    $context = stream_context_create($options);
    file_get_contents($url, false, $context);
}

// ====== أوامر البوت ======

if ($text === "/start") {

    sendMessage(
        $chat_id,
        "👋 أهلاً بك في بوت الخدمات\n\n".
        "🛒 لعرض الخدمات اكتب:\n".
        "/services"
    );

} elseif ($text === "/services") {

    sendMessage(
        $chat_id,
        "📦 الخدمات المتوفرة:\n\n".
        "1️⃣ شحن Telegram Premium\n".
        "2️⃣ شحن نجوم تيليجرام ⭐\n\n".
        "✍️ لتنفيذ طلب اكتب:\n".
        "/buy"
    );

} elseif ($text === "/buy") {

    // ====== مثال تنفيذ طلب (عدّله حسب API الحقيقي) ======
    $requestUrl =
        $apiUrl .
        "?username=" . urlencode($apiUser) .
        "&token="    . urlencode($apiToken) .
        "&service=telegram_test" .
        "&qty=1" .
        "&number=" . urlencode($chat_id);

    $response = @file_get_contents($requestUrl);

    if ($response === false) {
        sendMessage($chat_id, "❌ حدث خطأ أثناء الاتصال بالخدمة، حاول لاحقًا");
    } else {
        sendMessage(
            $chat_id,
            "✅ تم إرسال الطلب\n\n".
            "📄 رد الخدمة:\n".
            $response
        );
    }

} else {

    sendMessage(
        $chat_id,
        "❓ أمر غير معروف\n\n".
        "استخدم /services لعرض الخدمات"
    );

}
