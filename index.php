<?php
http_response_code(200);
set_time_limit(0);

// ================= CONFIG =================

$botToken = "8057785864:AAG-TggKI7ILG7JLSEwAuwz6F5WH7ddTne0";

$apiUrl  = "https://megatec-center.com/api/rest.php";
$apiUser = "u_3862970154";
$apiPass = "Fekri-738911634";

// ================= READ UPDATE =================

$update = json_decode(file_get_contents("php://input"), true);

if (!$update) {
    exit;
}

$message = $update["message"] ?? null;

if (!$message) {
    exit;
}

$chat_id = $message["chat"]["id"];
$text    = trim($message["text"] ?? "");

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

// ================= API FUNCTION =================

function sendOrder($service_id, $player_id) {
    global $apiUrl, $apiUser, $apiPass;

    $postData = [
        "request"   => "neworder",
        "service"   => $service_id,
        "player_id" => $player_id
    ];

    $ch = curl_init($apiUrl);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

    // 🔥 Basic Auth
    curl_setopt($ch, CURLOPT_USERPWD, $apiUser . ":" . $apiPass);

    // 🔥 مهم جداً
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        return [
            "status" => false,
            "message" => curl_error($ch)
        ];
    }

    curl_close($ch);

    return json_decode($response, true);
}

// ================= BOT LOGIC =================

if ($text === "/start") {

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

// استقبال Player ID
if (is_numeric($text) && file_exists("order_$chat_id.txt")) {

    $service = file_get_contents("order_$chat_id.txt");
    unlink("order_$chat_id.txt");

    sendMessage($chat_id, "⏳ جاري تنفيذ الطلب...");

    $result = sendOrder($service, $text);

    if (!$result) {
        sendMessage($chat_id, "❌ لم يتم استلام رد من السيرفر.");
        exit;
    }

    if (isset($result["status"]) && $result["status"] == true) {

        sendMessage(
            $chat_id,
            "✅ <b>تم تنفيذ الطلب بنجاح</b>\n\n📦 رقم الطلب:\n<code>" .
            ($result["order"] ?? "غير معروف") .
            "</code>"
        );

    } else {

        sendMessage(
            $chat_id,
            "❌ <b>فشل تنفيذ الطلب</b>\n\n📌 السبب:\n" .
            ($result["message"] ?? "خطأ غير معروف")
        );
    }

    exit;
}

// أي رسالة أخرى
sendMessage($chat_id, "ℹ️ أرسل /start للبدء");
