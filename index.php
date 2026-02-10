<?php
http_response_code(200);

// ================= CONFIG =================
$botToken = "8057785864:AAG-TggKI7ILG7JLSEwAuwz6F5WH7ddTne0";

$apiUrl   = "https://megatec-center.com/api/request";
$apiUser  = "u_3862970154"; // (غير مستخدم الآن لكن محفوظ)
$apiToken = "fpl08cFMtJKHk5niYZuqd9r6LyBV2QDCNmwWv1UeRXIxo";

// ================= READ UPDATE =================
$update = json_decode(file_get_contents("php://input"), true);

$message  = $update["message"] ?? null;
$callback = $update["callback_query"] ?? null;

if (!$message && !$callback) {
    exit;
}

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

// ================= START & TEXT =================
if ($message) {

    $chat_id = $message["chat"]["id"];
    $text    = trim($message["text"] ?? "");

    // /start
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

    // اختيار الباقة
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

        $reference = time() . rand(100,999);

        // ===== CURL POST =====
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
            sendMessage($chat_id, "❌ خطأ في الاتصال:\n$error");
            exit;
        }

        sendMessage(
            $chat_id,
            "✅ <b>تم إرسال طلب الشحن</b>\n\n📄 رد النظام:\n<pre>$response</pre>"
        );
        exit;
    }

    // أي رسالة أخرى
    sendMessage($chat_id, "ℹ️ للبدء أرسل /start");
}
