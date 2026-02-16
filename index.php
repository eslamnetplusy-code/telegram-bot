<?php

// ================= TELEGRAM CONFIG =================
$botToken = "8057785864:AAG-TggKI7ILG7JLSEwAuwz6F5WH7ddTne0";
$chat_id  = $_POST['chat_id'] ?? null;

// ================= MEGATEC CONFIG =================
$API_USER = "u_3862970154";
$API_KEY  = "fpl08cFMtJKHk5niYZuqd9r6LyBV2QDCNmwWv1UeRXIxo";
$API_URL  = "https://megatec-center.com/api/rest.php";

// ==================================================
// ارسال رسالة تيليجرام
function sendMessage($chat_id,$text){
    global $botToken;
    file_get_contents("https://api.telegram.org/bot$botToken/sendMessage?chat_id=$chat_id&text=".urlencode($text));
}

// ==================================================
// طلب CURL مع Basic Auth (هذا هو التصحيح المهم 🔥)
function callAPI($postData){

    global $API_USER,$API_KEY,$API_URL;

    $auth = base64_encode("$API_USER:$API_KEY");

    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => $API_URL,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_HTTPHEADER => [
            "Authorization: Basic $auth"
        ]
    ]);

    $response = curl_exec($curl);
    curl_close($curl);

    return $response;
}

// ==================================================
// استقبال تحديثات التليجرام
$update = json_decode(file_get_contents("php://input"), true);

if(!isset($update["message"])) exit;

$chat_id = $update["message"]["chat"]["id"];
$text = $update["message"]["text"];

// ==================================================
// START
if($text == "/start"){
    sendMessage($chat_id,"👋 مرحباً بك\nاختر باقة الشحن:");
}

// ==================================================
// طلب Player ID
elseif(is_numeric($text)){

    $player_id = $text;

    sendMessage($chat_id,"⏳ جاري تنفيذ الطلب...");

    // رقم الخدمة 1101 (60 UC)
    $service_id = "1101";
    $reference  = rand(100000,999999);

    $response = callAPI([
        "request"   => "neworder",
        "service"   => $service_id,
        "reference" => $reference,
        "player_id" => $player_id
    ]);

    $data = json_decode($response,true);

    if(!$data){
        sendMessage($chat_id,"❌ لم يتم الرد من السيرفر");
        exit;
    }

    if($data["status"] == true){
        sendMessage($chat_id,"✅ تم تنفيذ الطلب بنجاح\nرقم الطلب: ".$data["orderid"]);
    }else{
        sendMessage($chat_id,"❌ فشل تنفيذ الطلب\nالسبب:\n".$data["message"]);
    }
}

?>
