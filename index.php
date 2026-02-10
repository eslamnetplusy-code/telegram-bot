<?php
// تأكيد رد 200 فورًا
http_response_code(200);

// قراءة التحديث
$input = file_get_contents("php://input");
$update = json_decode($input, true);

// سجل للتأكد (اختياري)
file_put_contents("debug.log", $input . PHP_EOL, FILE_APPEND);

// توكن البوت
$botToken = getenv("BOT_TOKEN");
$apiUrl = "https://api.telegram.org/bot$botToken";

// تحقق من وجود رسالة
if (!isset($update["message"]["chat"]["id"])) {
    exit;
}

$chat_id = $update["message"]["chat"]["id"];
$text = trim($update["message"]["text"] ?? "");

// رد بسيط للاختبار
if ($text === "/start") {
    sendMessage($chat_id, "✅ البوت شغال الآن!\n\nأهلاً بك 👋");
} else {
    sendMessage($chat_id, "📩 وصلني:\n" . $text);
}

// دالة إرسال رسالة
function sendMessage($chat_id, $text) {
    global $apiUrl;

    $data = [
        "chat_id" => $chat_id,
        "text" => $text
    ];

    $options = [
        "http" => [
            "header"  => "Content-Type: application/json",
            "method"  => "POST",
            "content" => json_encode($data, JSON_UNESCAPED_UNICODE)
        ]
    ];

    file_get_contents($apiUrl . "/sendMessage", false, stream_context_create($options));
}
