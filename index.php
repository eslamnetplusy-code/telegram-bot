<?php

http_response_code(200); // مهم جداً
$content = file_get_contents("php://input");

if (!$content) {
    exit;
}

$update = json_decode($content, true);

if (!isset($update["message"])) {
    exit;
}

$chat_id = $update["message"]["chat"]["id"];
$text = $update["message"]["text"] ?? '';

if ($text == "/start") {

    $keyboard = [
        "keyboard" => [
            [["text" => "60 UC"], ["text" => "325 UC"]],
            [["text" => "660 UC"], ["text" => "1800 UC"]]
        ],
        "resize_keyboard" => true
    ];

    $data = [
        "chat_id" => $chat_id,
        "text" => "👋 مرحباً بك\n\nاختر باقة شحن شدّات ببجي:",
        "reply_markup" => json_encode($keyboard)
    ];

    file_get_contents("https://api.telegram.org/bot8057785864:AAG-TggKI7ILG7JLSEwAuwz6F5WH7ddTne0/sendMessage?" . http_build_query($data));
}

exit;
