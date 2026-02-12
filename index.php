<?php
http_response_code(200);

/* ================= CONFIG ================= */

$botToken = "8057785864:AAG-TggKI7ILG7JLSEwAuwz6F5WH7ddTne0";

$apiUrl   = "https://7175064b1acb.sn.mynetname.net:8080/rest";
$apiUser  = "u_3862970154";
$apiToken = "fpl08cFMtJKHk5niYZuqd9r6LyBV2QDCNmwWv1UeRXIxo";

/* ================= SEND MESSAGE ================= */

function sendMessage($chat_id, $text, $keyboard = null)
{
    global $botToken;

    $data = [
        "chat_id" => $chat_id,
        "text" => $text,
        "parse_mode" => "HTML"
    ];

    if ($keyboard) {
        $data["reply_markup"] = json_encode($keyboard, JSON_UNESCAPED_UNICODE);
    }

    file_get_contents(
        "https://api.telegram.org/bot$botToken/sendMessage?" .
        http_build_query($data)
    );
}

/* ================= YEMEN API (RAW DEBUG) ================= */

function yemenApi($data)
{
    global $apiUrl, $apiUser, $apiToken;

    $postData = [
        "username" => $apiUser,
        "token"    => $apiToken
    ] + $data;

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return "CURL ERROR: " . $error;
    }

    curl_close($ch);

    return $response; // نرجع الرد الخام
}

/* ================= READ UPDATE ================= */

$update = json_decode(file_get_contents("php://input"), true);
$message = $update["message"] ?? null;

if (!$message) exit;

$chat_id = $message["chat"]["id"];
$text = trim($message["text"] ?? "");

/* ================= START ================= */

if ($text == "/start") {

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

/* ================= SELECT PACKAGE ================= */

if ($text == "🎮 10 شدّات") {
    file_put_contents("order_$chat_id.txt", "1114");
    sendMessage($chat_id, "✍️ أرسل <b>Player ID</b> الآن:");
    exit;
}

if ($text == "🎮 60 شدّة") {
    file_put_contents("order_$chat_id.txt", "1101");
    sendMessage($chat_id, "✍️ أرسل <b>Player ID</b> الآن:");
    exit;
}

/* ================= RECEIVE PLAYER ID ================= */

if (is_numeric($text) && file_exists("order_$chat_id.txt")) {

    $service = file_get_contents("order_$chat_id.txt");
    unlink("order_$chat_id.txt");

    $reference = time() . rand(100,999);

    sendMessage($chat_id, "⏳ جاري تنفيذ الطلب...");

    $apiResponse = yemenApi([
        "request"   => "neworder",
        "service"   => $service,
        "reference" => $reference,
        "player_id" => $text
    ]);

    // نطبع الرد الخام
    sendMessage(
        $chat_id,
        "📄 <b>رد السيرفر:</b>\n<pre>$apiResponse</pre>"
    );

    exit;
}

/* ================= DEFAULT ================= */

sendMessage($chat_id, "ℹ️ أرسل /start للبدء");
