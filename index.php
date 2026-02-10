<?php
http_response_code(200);

$BOT_TOKEN = getenv("8057785864:AAG-TggKI7ILG7JLSEwAuwz6F5WH7ddTne0");
$API_URL = "http://185.112.200.88/yemen_robot";
$ADMIN_ID = 1442087030;

// API PUBG
$PUBG_API = "http://185.112.200.88/yemen_robot";

// فئات الشدّات
$PUBG_SERVICES = [
    "10" => "1114",
    "60" => "1101"
];

// قراءة التحديث
$update = json_decode(file_get_contents("php://input"), true);

// تخزين الحالات
$stateFile = "state.json";
$states = file_exists($stateFile) ? json_decode(file_get_contents($stateFile), true) : [];

// ===============================
// دوال مساعدة
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

function saveStates($states) {
    file_put_contents("state.json", json_encode($states));
}

// تنفيذ طلب PUBG تلقائي
function chargePubg($service_id, $player_id) {
    global $PUBG_API;

    $postData = http_build_query([
        "service"   => $service_id,
        "player_id" => $player_id
    ]);

    $opts = [
        "http" => [
            "method"  => "POST",
            "header"  => "Content-Type: application/x-www-form-urlencoded",
            "content" => $postData,
            "timeout" => 30
        ]
    ];

    $context = stream_context_create($opts);
    return file_get_contents($PUBG_API, false, $context);
}

// ===============================
// أزرار المستخدم
// ===============================
$mainKeyboard = [
    "inline_keyboard" => [
        [
            ["text" => "🎮 شحن شدّات ببجي", "callback_data" => "pubg"]
        ]
    ]
];

// ===============================
// الرسائل النصية
// ===============================
if (isset($update["message"])) {

    $chat_id = $update["message"]["chat"]["id"];
    $text = trim($update["message"]["text"] ?? "");

    if ($text === "/start") {
        sendMessage($chat_id, "👋 أهلاً بك\nاختر الخدمة:", $mainKeyboard);
        exit;
    }

    // إدخال Player ID
    if (isset($states[$chat_id]) && $states[$chat_id]["step"] === "pubg_player") {
        $states[$chat_id]["player_id"] = $text;
        $states[$chat_id]["step"] = "pubg_amount";
        saveStates($states);

        $keyboard = [
            "inline_keyboard" => [
                [
                    ["text" => "10 شدّات", "callback_data" => "pubg_amount|10"],
                    ["text" => "60 شدّات", "callback_data" => "pubg_amount|60"]
                ]
            ]
        ];

        sendMessage($chat_id, "🎮 اختر فئة الشحن:", $keyboard);
        exit;
    }
}

// ===============================
// الأزرار
// ===============================
if (isset($update["callback_query"])) {

    $chat_id = $update["callback_query"]["message"]["chat"]["id"];
    $data = $update["callback_query"]["data"];

    // بدء PUBG
    if ($data === "pubg") {
        $states[$chat_id] = ["step" => "pubg_player"];
        saveStates($states);

        sendMessage($chat_id, "✍️ أرسل Player ID:");
        exit;
    }

    // اختيار الفئة
    if (strpos($data, "pubg_amount") === 0) {
        [, $amount] = explode("|", $data);

        $player_id = $states[$chat_id]["player_id"];
        $service_id = $GLOBALS["PUBG_SERVICES"][$amount];

        unset($states[$chat_id]);
        saveStates($states);

        // إرسال الطلب للأدمن مع زر التنفيذ
        $adminKeyboard = [
            "inline_keyboard" => [
                [
                    ["text" => "🟢 تم التنفيذ", "callback_data" => "pubg_exec|$chat_id|$service_id|$player_id"]
                ]
            ]
        ];

        sendMessage(
            $ADMIN_ID,
            "📩 <b>طلب شحن ببجي</b>\n\n".
            "🆔 Player ID: $player_id\n".
            "🎮 الفئة: $amount شدّات\n",
            $adminKeyboard
        );

        sendMessage($chat_id, "✅ تم استلام طلبك وسيتم تنفيذه قريبًا");
        exit;
    }

    // تنفيذ تلقائي (أدمن)
    if (strpos($data, "pubg_exec") === 0 && $chat_id == $ADMIN_ID) {

        [, $user_chat, $service_id, $player_id] = explode("|", $data);

        $result = chargePubg($service_id, $player_id);

        sendMessage($user_chat, "🎉 تم تنفيذ طلبك\n\n📄 رد النظام:\n$result");
        sendMessage($ADMIN_ID, "✅ تم تنفيذ الطلب بنجاح\n\n$result");
        exit;
    }
}
