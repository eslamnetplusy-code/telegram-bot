<?php
http_response_code(200);

/* ================== CONFIG ================== */

$botToken = "8057785864:AAG-TggKI7ILG7JLSEwAuwz6F5WH7ddTne0";
$apiUrl   = "https://megatec-center.com/api/rest.php";

$apiUser  = "u_3862970154";
$apiPass  = "Fekri-738911634";

/* ================== TELEGRAM FUNCTION ================== */

function sendMessage($chat_id, $text, $keyboard = null)
{
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

/* ================== MEGATEC API FUNCTION ================== */

function megaApi($postData)
{
    global $apiUrl, $apiUser, $apiPass;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_USERPWD, $apiUser . ":" . $apiPass);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

/* ================== READ UPDATE ================== */

$update = json_decode(file_get_contents("php://input"), true);
$message = $update["message"] ?? null;

if (!$message) exit;

$chat_id = $message["chat"]["id"];
$text = trim($message["text"] ?? "");

/* ================== START ================== */

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

/* ================== SELECT SERVICE ================== */

if ($text == "🎮 10 شدّات") {
    file_put_contents("order_$chat_id.txt", "1114"); // رقم API للخدمة 10
    sendMessage($chat_id, "✍️ أرسل <b>Player ID</b> الآن:");
    exit;
}

if ($text == "🎮 60 شدّة") {
    file_put_contents("order_$chat_id.txt", "1101"); // رقم API للخدمة 60
    sendMessage($chat_id, "✍️ أرسل <b>Player ID</b> الآن:");
    exit;
}

/* ================== RECEIVE PLAYER ID ================== */

if (is_numeric($text) && file_exists("order_$chat_id.txt")) {

    $service = file_get_contents("order_$chat_id.txt");
    unlink("order_$chat_id.txt");

    $reference = time() . rand(100,999);

    $apiResponse = megaApi([
        "request"   => "neworder",
        "service"   => $service,
        "reference" => $reference,
        "player_id" => $text
    ]);

    if (!$apiResponse || $apiResponse["status"] == false) {

        $errorMsg = $apiResponse["message"] ?? "حدث خطأ غير معروف";

        sendMessage(
            $chat_id,
            "❌ <b>فشل تنفيذ الطلب</b>\n\n📌 السبب:\n<pre>$errorMsg</pre>"
        );

        exit;
    }

    sendMessage(
        $chat_id,
        "✅ <b>تم تنفيذ طلب الشحن بنجاح</b>\n\n🧾 رقم العملية:\n<code>{$reference}</code>"
    );

    exit;
}

/* ================== DEFAULT ================== */

sendMessage($chat_id, "ℹ️ أرسل /start للبدء");
