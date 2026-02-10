<?php
http_response_code(200);

$botToken = "8057785864:AAG-TggKI7ILG7JLSEwAuwz6F5WH7ddTne0";

// اقرأ التحديث
$update = json_decode(file_get_contents("php://input"), true);

// إذا ما في رسالة → خروج
if (!isset($update["message"])) {
    exit;
}

$chat_id = $update["message"]["chat"]["id"];
$text    = $update["message"]["text"] ?? "no text";

// إرسال رد مباشر
$ch = curl_init("https://api.telegram.org/bot$botToken/sendMessage");
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTFIELDS => [
        "chat_id" => $chat_id,
        "text" => "✅ Railway OK\nرسالتك: $text"
    ]
]);
curl_exec($ch);
curl_close($ch);
