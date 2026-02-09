<?php

$botToken = getenv("8057785864:AAG-TggKI7ILG7JLSEwAuwz6F5WH7ddTne0");
$apiUrl   = getenv("http://185.112.200.88/yemen_robot");
$apiUser  = getenv("u_3862970154");
$apiToken = getenv("fpl08cFMtJKHk5niYZuqd9r6LyBV2QDCNmwWv1UeRXIxo");

$update = json_decode(file_get_contents("php://input"), true);
$message = $update["message"] ?? null;

if (!$message) exit;

$chat_id = $message["chat"]["id"];
$text    = trim($message["text"] ?? "");

function sendMessage($chat_id, $text) {
    global $botToken;
    file_get_contents(
        "https://api.telegram.org/bot$botToken/sendMessage?" .
        http_build_query([
            "chat_id" => $chat_id,
            "text" => $text
        ])
    );
}

if ($text === "/start") {
    sendMessage($chat_id, "👋 أهلاً بك\n\nاكتب /services لعرض الخدمات");
}

elseif ($text === "/services") {
    sendMessage($chat_id,
        "🛒 الخدمات المتوفرة:\n" .
        "1️⃣ شحن Telegram Premium\n" .
        "2️⃣ شحن نجوم تيليجرام\n\n" .
        "اكتب /buy للطلب"
    );
}

elseif ($text === "/buy") {
    // مثال تنفيذ طلب
    $url = $apiUrl . "?username=$apiUser&token=$apiToken&service=test&qty=1&number=$chat_id";
    $response = file_get_contents($url);

    sendMessage($chat_id, "📦 نتيجة الطلب:\n$response");
}

else {
    sendMessage($chat_id, "❓ أمر غير معروف");
}
