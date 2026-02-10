<?php
http_response_code(200);

$BOT_TOKEN = getenv("BOT_TOKEN");
$API_URL = "https://api.telegram.org/bot$BOT_TOKEN/";
$ADMIN_ID = 1442087030;

// قراءة التحديث
$update = json_decode(file_get_contents("php://input"), true);

// ملف تخزين مؤقت بسيط للحالة
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

// ===============================
// أزرار البداية
// ===============================
$mainKeyboard = [
    "inline_keyboard" => [
        [
            ["text" => "⭐ شحن Telegram Premium", "callback_data" => "tg_premium"]
        ],
        [
            ["text" => "☎️ الدعم الفني", "callback_data" => "support"]
        ]
    ]
];

// ===============================
// معالجة الرسائل النصية
// ===============================
if (isset($update["message"])) {

    $chat_id = $update["message"]["chat"]["id"];
    $text = trim($update["message"]["text"] ?? "");

    // /start
    if ($text === "/start") {
        sendMessage(
            $chat_id,
            "👋 أهلاً بك\n\nاختر الخدمة المطلوبة:",
            $mainKeyboard
        );
        exit;
    }

    // استقبال اسم المستخدم
    if (isset($states[$chat_id]) && $states[$chat_id]["step"] === "username") {
        $states[$chat_id]["username"] = $text;
        $states[$chat_id]["step"] = "duration";
        saveStates($states);

        sendMessage(
            $chat_id,
            "⏳ اختر مدة الاشتراك:\n\n1️⃣ شهر\n3️⃣ ثلاثة أشهر\n12️⃣ سنة\n\nاكتب الرقم فقط"
        );
        exit;
    }

    // استقبال المدة
    if (isset($states[$chat_id]) && $states[$chat_id]["step"] === "duration") {
        $duration = $text;
        $username = $states[$chat_id]["username"];

        unset($states[$chat_id]);
        saveStates($states);

        // إرسال الطلب للأدمن
        sendMessage(
            $GLOBALS["ADMIN_ID"],
            "📩 <b>طلب شحن جديد</b>\n\n".
            "👤 المستخدم: @$username\n".
            "⭐ الخدمة: Telegram Premium\n".
            "⏳ المدة: $duration\n".
            "🆔 Chat ID: $chat_id"
        );

        // تأكيد للمستخدم
        sendMessage(
            $chat_id,
            "✅ تم استلام طلبك بنجاح\n\nسيتم تنفيذه يدويًا في أقرب وقت 🌟"
        );
        exit;
    }
}

// ===============================
// معالجة الأزرار
// ===============================
if (isset($update["callback_query"])) {

    $chat_id = $update["callback_query"]["message"]["chat"]["id"];
    $data = $update["callback_query"]["data"];

    if ($data === "tg_premium") {
        $states[$chat_id] = ["step" => "username"];
        saveStates($states);

        sendMessage(
            $chat_id,
            "⭐ شحن Telegram Premium\n\n✍️ أرسل اسم المستخدم أو الرقم:"
        );
    }

    if ($data === "support") {
        sendMessage(
            $chat_id,
            "☎️ الدعم الفني\n\nراسلنا مباشرة"
        );
    }
}
