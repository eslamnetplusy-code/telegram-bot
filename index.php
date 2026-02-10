<?php
http_response_code(200);

$BOT_TOKEN = getenv("BOT_TOKEN");
$API_URL = "https://api.telegram.org/bot$BOT_TOKEN/";
$ADMIN_ID = 1442087030;

// قراءة التحديث
$update = json_decode(file_get_contents("php://input"), true);

// ملف تخزين الحالة
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
// الأزرار الرئيسية
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
// معالجة الرسائل
// ===============================
if (isset($update["message"])) {

    $chat_id = $update["message"]["chat"]["id"];
    $text = trim($update["message"]["text"] ?? "");

    if ($text === "/start") {
        sendMessage($chat_id, "👋 أهلاً بك\n\nاختر الخدمة:", $mainKeyboard);
        exit;
    }

    if (isset($states[$chat_id]) && $states[$chat_id]["step"] === "username") {
        $states[$chat_id]["username"] = $text;
        $states[$chat_id]["step"] = "duration";
        saveStates($states);

        sendMessage($chat_id, "⏳ اختر المدة:\n1️⃣ شهر\n3️⃣ ثلاثة أشهر\n12️⃣ سنة\n\nاكتب الرقم فقط");
        exit;
    }

    if (isset($states[$chat_id]) && $states[$chat_id]["step"] === "duration") {
        $duration = $text;
        $username = $states[$chat_id]["username"];

        unset($states[$chat_id]);
        saveStates($states);

        // أزرار الأدمن
        $adminKeyboard = [
            "inline_keyboard" => [
                [
                    ["text" => "✅ قبول", "callback_data" => "approve|$chat_id"],
                    ["text" => "🔄 قيد التجهيز", "callback_data" => "processing|$chat_id"]
                ],
                [
                    ["text" => "❌ رفض", "callback_data" => "reject|$chat_id"]
                ]
            ]
        ];

        // إرسال الطلب للأدمن
        sendMessage(
            $ADMIN_ID,
            "📩 <b>طلب جديد</b>\n\n".
            "👤 المستخدم: @$username\n".
            "⭐ الخدمة: Telegram Premium\n".
            "⏳ المدة: $duration\n".
            "🆔 Chat ID: $chat_id",
            $adminKeyboard
        );

        sendMessage($chat_id, "✅ تم استلام طلبك وسيتم مراجعته ✨");
        exit;
    }
}

// ===============================
// معالجة الأزرار
// ===============================
if (isset($update["callback_query"])) {

    $data = $update["callback_query"]["data"];
    $admin_chat = $update["callback_query"]["message"]["chat"]["id"];

    // أزرار المستخدم
    if ($data === "tg_premium") {
        $states[$admin_chat] = ["step" => "username"];
        saveStates($states);

        sendMessage($admin_chat, "⭐ أرسل اسم المستخدم أو الرقم:");
        exit;
    }

    if ($data === "support") {
        sendMessage($admin_chat, "☎️ الدعم الفني\nراسلنا في أي وقت");
        exit;
    }

    // أزرار الأدمن
    if ($admin_chat == $GLOBALS["ADMIN_ID"]) {

        list($action, $user_chat) = explode("|", $data);

        if ($action === "approve") {
            sendMessage($user_chat, "🎉 تم <b>قبول</b> طلبك وسيتم التنفيذ قريبًا");
            sendMessage($admin_chat, "✅ تم قبول الطلب");
        }

        if ($action === "processing") {
            sendMessage($user_chat, "🔄 طلبك <b>قيد التجهيز</b> حاليًا");
            sendMessage($admin_chat, "🔄 تم تحديث الحالة: قيد التجهيز");
        }

        if ($action === "reject") {
            sendMessage($user_chat, "❌ نعتذر، تم <b>رفض</b> الطلب\nللاستفسار تواصل مع الدعم");
            sendMessage($admin_chat, "❌ تم رفض الطلب");
        }
    }
}
