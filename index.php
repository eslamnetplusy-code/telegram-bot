<?php

// ===============================
// إعدادات البوت
// ===============================
$BOT_TOKEN = getenv("BOT_TOKEN"); // سنضعه في Railway
$API_URL = "https://api.telegram.org/bot$BOT_TOKEN/";

// قراءة التحديث القادم من تيليجرام
$update = json_decode(file_get_contents("php://input"), true);

// تسجيل التحديثات (للتأكد أن webhook شغال)
file_put_contents("log.txt", print_r($update, true), FILE_APPEND);

// ===============================
// دالة إرسال رسالة
// ===============================
function sendMessage($chat_id, $text, $keyboard = null) {
    global $API_URL;

    $data = [
        "chat_id" => $chat_id,
        "text" => $text,
        "parse_mode" => "HTML"
    ];

    if ($keyboard) {
        $data["reply_markup"] = json_encode($keyboard);
    }

    file_get_contents($API_URL . "sendMessage?" . http_build_query($data));
}

// ===============================
// معالجة الرسائل
// ===============================
if (isset($update["message"])) {

    $chat_id = $update["message"]["chat"]["id"];
    $text = $update["message"]["text"] ?? "";

    if ($text === "/start") {

        $keyboard = [
            "inline_keyboard" => [
                [
                    ["text" => "💳 شحن رصيد", "callback_data" => "charge_balance"]
                ],
                [
                    ["text" => "⭐ شحن Telegram Premium", "callback_data" => "telegram_premium"]
                ],
                [
                    ["text" => "🎮 شحن ألعاب", "callback_data" => "games"]
                ],
                [
                    ["text" => "☎️ الدعم الفني", "callback_data" => "support"]
                ]
            ]
        ];

        sendMessage(
            $chat_id,
            "✅ <b>البوت شغال الآن!</b>\n\nأهلاً بك 👋\nاختر الخدمة المطلوبة:",
            $keyboard
        );
    }
}

// ===============================
// معالجة الأزرار
// ===============================
if (isset($update["callback_query"])) {

    $chat_id = $update["callback_query"]["message"]["chat"]["id"];
    $data = $update["callback_query"]["data"];

    switch ($data) {
        case "charge_balance":
            sendMessage($chat_id, "💳 خدمة شحن الرصيد\n\n(سيتم تفعيلها قريبًا)");
            break;

        case "telegram_premium":
            sendMessage($chat_id, "⭐ شحن Telegram Premium\n\n(سيتم تفعيلها قريبًا)");
            break;

        case "games":
            sendMessage($chat_id, "🎮 شحن الألعاب\n\n(سيتم تفعيلها قريبًا)");
            break;

        case "support":
            sendMessage($chat_id, "☎️ الدعم الفني\n\nراسلنا على: @YourSupport");
            break;
    }
}
