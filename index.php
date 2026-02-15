<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

/*===============================
    بيانات البوت
================================*/
$BOT_TOKEN ="8057785864:AAG-TggKI7ILG7JLSEwAuwz6F5WH7ddTne0";
$API_TELEGRAM = "https://api.telegram.org/bot$BOT_TOKEN";

/*===============================
    قراءة تحديث تيليجرام
================================*/
$update = json_decode(file_get_contents("php://input"), true);

if(!$update){
    exit;
}

$message = $update["message"] ?? null;
$chat_id = $message["chat"]["id"] ?? null;
$text = $message["text"] ?? null;

if(!$chat_id) exit;

/*===============================
    ارسال رسالة
================================*/
function sendMessage($chat_id,$text,$keyboard=null){
    global $API_TELEGRAM;

    $data = [
        "chat_id"=>$chat_id,
        "text"=>$text,
        "parse_mode"=>"HTML"
    ];

    if($keyboard){
        $data["reply_markup"]=json_encode($keyboard);
    }

    file_get_contents($API_TELEGRAM."/sendMessage?".http_build_query($data));
}

/*===============================
    القائمة الرئيسية
================================*/
function showMenu($chat_id){
    $keyboard = [
        "keyboard"=>[
            [["text"=>"10 شدات 🎮"],["text"=>"60 شدة 🎮"]]
        ],
        "resize_keyboard"=>true
    ];

    sendMessage($chat_id,"👋 مرحباً بك\n\nاختر باقة شحن شدات ببجي:",$keyboard);
}

/*===============================
    تنفيذ الطلب الحقيقي (المأمون)
================================*/
function sendOrder($player_id){

    $url = "https://almamon.yemoney.net/api/yr/";

    $data = [
        "username" => "777438844",
        "password" => "Fekri-738911634",
        "account"  => "6482",
        "ip"       => "185.11.8.23",

        // نوع العملية (تعديل حسب المزود اذا اعطاك اسم العملية)
        "action"   => "pubg",
        "amount"   => "60",
        "player_id"=> $player_id
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch,[
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if($error){
        return "❌ خطأ اتصال بالسيرفر";
    }

    if(!$response){
        return "❌ لا يوجد رد من المزود";
    }

    return "✅ تم إرسال الطلب للمزود\n\n📩 الرد:\n".$response;
}

/*===============================
    منطق البوت
================================*/

if($text == "/start"){
    showMenu($chat_id);
    exit;
}

if($text == "10 شدات 🎮"){
    file_put_contents("step_$chat_id.txt","WAIT_ID_10");
    sendMessage($chat_id,"📌 أرسل Player ID الآن:");
    exit;
}

if($text == "60 شدة 🎮"){
    file_put_contents("step_$chat_id.txt","WAIT_ID_60");
    sendMessage($chat_id,"📌 أرسل Player ID الآن:");
    exit;
}

/*===============================
    استقبال Player ID
================================*/
$stepFile = "step_$chat_id.txt";

if(file_exists($stepFile)){
    $step = file_get_contents($stepFile);

    if($step == "WAIT_ID_10" || $step == "WAIT_ID_60"){
        unlink($stepFile);

        sendMessage($chat_id,"⏳ جاري تنفيذ الطلب...");

        $result = sendOrder($text);

        sendMessage($chat_id,$result);
        showMenu($chat_id);
        exit;
    }
}

showMenu($chat_id);
?>
