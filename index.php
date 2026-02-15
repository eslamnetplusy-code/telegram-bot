<?php
// ============================================
// بوت تليجرام + Megatec Center API
// متوافق مع استضافة Replit
// ============================================

// تحميل المتغيرات السرية
$BOT_TOKEN = getenv('8057785864:AAG-TggKI7ILG7JLSEwAuwz6F5WH7ddTne0');
$MEGATEC_URL = 'https://megatec-center.com/api/rest.php';
$MEGATEC_TOKEN = getenv('fpl08cFMtJKHk5niYZuqd9r6LyBV2QDCNmwWv1UeRXIxo');
$MEGATEC_USER = getenv('u_3862970154');
$AUTHORIZED_USERS = array_filter(explode(',', getenv('AUTHORIZED_USERS') ?: ''));

// ============================================
// معالجة طلبات GET (لضبط Webhook)
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['set_webhook'])) {
        $current_url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $webhook_url = str_replace('?set_webhook=1', '', $current_url);
        
        $response = file_get_contents("https://api.telegram.org/bot$BOT_TOKEN/setWebhook?url=" . urlencode($webhook_url));
        echo "✅ تم ضبط Webhook بنجاح!\nالرابط: $webhook_url\nالرد من تليجرام:\n" . $response;
        exit;
    }
    
    echo "🤖 بوت شحن الألعاب يعمل على Replit\n\n";
    echo "لتفعيل البوت:\n1. اضبط Webhook بزيارة:\n   {$current_url}?set_webhook=1\n";
    echo "2. أرسل /start للبوت";
    exit;
}

// ============================================
// معالجة تحديثات تليجرام (Webhook)
// ============================================
$update = json_decode(file_get_contents('php://input'), true);
if (!$update || !isset($update['message'])) exit;

$message = $update['message'];
$chat_id = $message['chat']['id'];
$user_id = $message['from']['id'];
$text = trim($message['text'] ?? '');

// 🔒 التحقق من الصلاحية
if (!empty($AUTHORIZED_USERS) && !in_array((string)$user_id, $AUTHORIZED_USERS)) {
    send_telegram($chat_id, "🚫 أنت غير مصرح لك باستخدام هذا البوت.");
    exit;
}

// ============================================// معالجة الأوامر
// ============================================
switch ($text) {
    case '/start':
        $reply = "🎮 مرحباً بك في بوت شحن الألعاب!\n\n" .
                 "الأوامر المتاحة:\n" .
                 "/services - عرض الخدمات\n" .
                 "/order [id] [player_id] - شحن خدمة\n" .
                 "/help - مساعدة";
        send_telegram($chat_id, $reply);
        break;
        
    case '/services':
        $services = megatec_api(['request' => 'servicelist']);
        
        if (!$services || !$services['status'] ?? false) {
            send_telegram($chat_id, "❌ فشل جلب الخدمات. حاول لاحقاً.");
            break;
        }
        
        $msg = "🎮 *الخدمات المتاحة* 🎮\n------------------\n";
        foreach ($services['ServiceList'] as $svc) {
            if ($svc['ServiceApiID'] == 0) continue; // تخطي الخدمة المخصصة
            
            $price = number_format($svc['Price'], 2);
            $msg .= sprintf(
                "🆔 `%d` | 💰 \$%s | %s\n",
                $svc['ServiceApiID'],
                $price,
                str_replace(['[', ']'], '', $svc['ServiceName'])
            );
        }
        $msg .= "\n💡 لشراء خدمة: `/order [id] [player_id]`";
        send_telegram($chat_id, $msg, 'MarkdownV2');
        break;
        
    case '/help':
        $reply = "ℹ️ *طريقة الاستخدام*\n" .
                 "1️⃣ احصل على معرف الخدمة من /services\n" .
                 "2️⃣ أرسل الأمر:\n   `/order 1101 123456789`\n\n" .
                 "🎮 ألعاب مدعومة:\n" .
                 "• PUBG Mobile\n• Free Fire\n• Mobile Legends (يتطلب zone_id)\n" .
                 "• Razer Gold\n• PlayStation Network";
        send_telegram($chat_id, $reply, 'MarkdownV2');
        break;
        
    default:
        if (preg_match('/^\/order\s+(\d+)\s+(\S+)(?:\s+(\S+))?$/', $text, $m)) {
            $service_id = $m[1];
            $player_id = $m[2];            $zone_id = $m[3] ?? '';
            
            // التحقق من صحة المدخلات
            if (!ctype_digit($service_id) || strlen($player_id) > 50) {
                send_telegram($chat_id, "❌ معلمات غير صالحة. تأكد من التنسيق.");
                break;
            }
            
            // إعداد طلب الشراء
            $params = [
                'request' => 'order',
                'service_id' => $service_id,
                'player_id' => $player_id,
                'api_token' => $MEGATEC_TOKEN,
                'username' => $MEGATEC_USER
            ];
            
            if (!empty($zone_id)) $params['zone_id'] = $zone_id;
            
            $result = megatec_api($params);
            
            if ($result['status'] ?? false) {
                $oid = $result['order_id'] ?? 'N/A';
                $reply = "✅ تم الشراء بنجاح!\n" .
                         "رقم الطلب: `$oid`\n" .
                         "سيتم الشحن خلال دقائق ⏱";
            } else {
                $err = $result['message'] ?? 'خطأ غير معروف';
                $reply = "❌ فشل الشراء:\n$err";
            }
            send_telegram($chat_id, $reply, 'MarkdownV2');
        } else {
            send_telegram($chat_id, "❓ أمر غير معروف. أرسل /start للقائمة.");
        }
}

// ============================================
// دوال مساعدة
// ============================================
function send_telegram($chat_id, $text, $parse_mode = 'Markdown') {
    global $BOT_TOKEN;
    
    // تنظيف النص لـ MarkdownV2
    if ($parse_mode === 'MarkdownV2') {
        $text = preg_replace('/([._*()~`>#+\-=|{}.!])/m', '\\\\$1', $text);
    }
    
    $url = "https://api.telegram.org/bot$BOT_TOKEN/sendMessage";
    $data = http_build_query([
        'chat_id' => $chat_id,        'text' => $text,
        'parse_mode' => $parse_mode,
        'disable_web_page_preview' => true
    ]);
    
    file_get_contents($url, false, stream_context_create([
        'http' => ['method' => 'POST', 'header' => 'Content-Type: application/x-www-form-urlencoded', 'content' => $data]
    ]));
}

function megatec_api($params) {
    global $MEGATEC_URL;
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $MEGATEC_URL,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($params),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
    ]);
    
    $res = curl_exec($ch);
    curl_close($ch);
    
    return $res ? json_decode($res, true) : ['error' => 'فشل الاتصال'];
}
?>
