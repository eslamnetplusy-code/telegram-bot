<?php
/**
 * Telegram Bot - Mega Center API Integration
 * Hosted on Railway
 */

require 'vendor/autoload.php';
require 'mega_api.php';

use \Telegram\Bot\Api;

// Initialize Telegram Bot
$botToken = getenv('8057785864:AAG-TggKI7ILG7JLSEwAuwz6F5WH7ddTne0');
$telegram = new Api($botToken);

// Initialize Mega API
$megaApi = new MegaAPI();

// Get Update
$update = $telegram->getWebhookUpdate();

// Check if update exists
if (!$update) {
    http_response_code(200);
    exit;
}

$message = $update->getMessage();
if (!$message) {
    http_response_code(200);
    exit;
}

$chatId = $message->getChat()->getId();
$userId = $message->getFrom()->getId();
$text = $message->getText();

/**
 * Send Message Helper
 */
function sendMessage($telegram, $chatId, $text) {
    return $telegram->sendMessage([
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'HTML'
    ]);
}

/**
 * Command: /start */
if ($text === '/start') {
    $welcomeMessage = "
🎮 <b>مرحباً بك في بوت الشحن!</b>

📌 <b>الأوامر المتاحة:</b>
/services - عرض الخدمات
/balance - فحص الرصيد
/order - طلب خدمة
/status - حالة الطلب
/help - المساعدة

💡 <b>للبدء:</b>
استخدم /services لعرض الخدمات المتاحة
    ";
    
    sendMessage($telegram, $chatId, $welcomeMessage);
}

/**
 * Command: /balance
 */
elseif ($text === '/balance') {
    $balance = $megaApi->getBalance();
    
    if ($balance['status'] === true) {
        $message = "
💰 <b>رصيد حسابك:</b>

💵 <code>{$balance['balance']} $</code>
        ";
    } else {
        $code = $balance['code'] ?? 'Unknown';
        $message = "
❌ <b>خطأ في فحص الرصيد</b>

🔴 كود الخطأ: <code>{$code}</code>
📝 {$balance['message']}
        ";
    }
    
    sendMessage($telegram, $chatId, $message);
}

/**
 * Command: /services
 */
elseif ($text === '/services') {
    $services = $megaApi->getServiceList();
        if ($services['status'] === true && isset($services['ServiceList'])) {
        $message = "📋 <b>الخدمات المتاحة:</b>\n\n";
        
        $count = 0;
        foreach ($services['ServiceList'] as $service) {
            if ($count >= 15) {
                $message .= "\n⚠️ <i>تم عرض أول 15 خدمة</i>";
                break;
            }
            
            $serviceName = $service['ServiceName'] ?? 'Unknown';
            $serviceId = $service['ServiceApiID'] ?? 'N/A';
            $price = $service['Price'] ?? '0';
            $doTime = $service['DoTime'] ?? 'N/A';
            
            $message .= "
🎮 <b>{$serviceName}</b>
🆔 <code>{$serviceId}</code>
💰 <code>{$price}$</code>
⏱️ {$doTime}
━━━━━━━━━━━━
            ";
            $count++;
        }
        
        $message .= "

💡 <b>للطلب:</b>
/order <service_id> <player_id>

مثال:
/order 1101 5687489561
        ";
    } else {
        $message = "❌ <b>فشل في جلب الخدمات</b>";
    }
    
    sendMessage($telegram, $chatId, $message);
}

/**
 * Command: /order
 */
elseif (strpos($text, '/order') === 0) {
    $parts = explode(' ', trim($text));
    
    if (count($parts) < 3) {
        $message = "
❌ <b>بيانات غير مكتملة!</b>
📝 <b>الاستخدام:</b>
/order <service_id> <player_id>

📌 <b>مثال:</b>
/order 1101 5687489561

💡 <b>للحصول على كود الخدمة:</b>
/services
        ";
        sendMessage($telegram, $chatId, $message);
    } else {
        $serviceId = $parts[1];
        $playerId = $parts[2];
        
        // Generate unique reference
        $reference = time() . '_' . $userId . '_' . rand(1000, 9999);
        
        // Place order
        $result = $megaApi->placeOrder($serviceId, $playerId, $reference);
        
        if ($result['status'] === true && isset($result['code']) && $result['code'] == 201) {
            $orderId = $result['orderid'];
            $price = $result['price'];
            
            $message = "
✅ <b>تم قبول الطلب!</b>

📋 <b>التفاصيل:</b>
🆔 رقم الطلب: <code>{$orderId}</code>
💰 السعر: <code>{$price}$</code>
🎮 اللاعب: <code>{$playerId}</code>
📦 الخدمة: <code>{$serviceId}</code>

💡 <b>لفحص الحالة:</b>
/status {$orderId}
            ";
        } else {
            $code = $result['code'] ?? 'Unknown';
            $msg = $result['message'] ?? 'خطأ غير معروف';
            
            // Error descriptions
            $errorDesc = '';
            switch ($code) {
                case 401:
                    $errorDesc = "⚠️ بيانات الدخول غير صحيحة";
                    break;
                case 405:
                    $errorDesc = "⚠️ رصيد غير كافي";
                    break;
                case 412:                    $errorDesc = "⚠️ الرقم المرجعي مكرر";
                    break;
                case 414:
                    $errorDesc = "⚠️ بيانات اللاعب غير صحيحة";
                    break;
                case 409:
                    $errorDesc = "⚠️ الخدمة غير متاحة";
                    break;
                case 415:
                    $errorDesc = "⚠️ النظام تحت الصيانة";
                    break;
            }
            
            $message = "
❌ <b>فشل الطلب!</b>

🔴 <b>كود الخطأ:</b> <code>{$code}</code>
📝 <b>التفاصيل:</b> {$msg}
{$errorDesc}
            ";
        }
        
        sendMessage($telegram, $chatId, $message);
    }
}

/**
 * Command: /status
 */
elseif (strpos($text, '/status') === 0) {
    $parts = explode(' ', trim($text));
    
    if (count($parts) < 2) {
        $message = "
❌ <b>أدخل رقم الطلب!</b>

📝 <b>الاستخدام:</b>
/status <order_id>

📌 <b>مثال:</b>
/status 14563
        ";
        sendMessage($telegram, $chatId, $message);
    } else {
        $orderId = $parts[1];
        $status = $megaApi->checkOrderStatus($orderId);
        
        if ($status['status'] === true) {
            $progress = $status['progress'] ?? 0;
            $msg = $status['msg'] ?? '';            $result = $status['result'] ?? '';
            
            // Progress status
            $progressInfo = [
                1 => ['📥', 'تم استلام الطلب'],
                2 => ['⚙️', 'جاري المعالجة'],
                4 => ['✅', 'تم بنجاح'],
                5 => ['❌', 'فشل / تم الاسترداد']
            ];
            
            $emoji = $progressInfo[$progress][0] ?? '⏳';
            $statusText = $progressInfo[$progress][1] ?? 'حالة غير معروفة';
            
            $message = "
📋 <b>حالة الطلب:</b>

🆔 رقم الطلب: <code>{$orderId}</code>
{$emoji} <b>الحالة:</b> {$statusText}
📝 <b>التفاصيل:</b> {$msg}
            ";
            
            if ($result && $progress == 4) {
                $message .= "

🎁 <b>النتيجة:</b>
<code>{$result}</code>
                ";
            }
        } else {
            $code = $status['code'] ?? 'Unknown';
            $message = "
❌ <b>فشل في فحص الحالة</b>

🔴 كود الخطأ: <code>{$code}</code>
📝 {$status['msg']}
            ";
        }
        
        sendMessage($telegram, $chatId, $message);
    }
}

/**
 * Command: /help
 */
elseif ($text === '/help') {
    $message = "
📚 <b>مركز المساعدة</b>

🎮 <b>الأوامر:</b>/start - البدء
/services - عرض الخدمات
/balance - فحص الرصيد
/order - طلب خدمة
/status - فحص الحالة
/help - المساعدة

📝 <b>كيفية الطلب:</b>
1. /services لعرض الخدمات
2. اختر الخدمة وانسخ كودها
3. /order <code><service_id></code> <code><player_id></code>
4. استخدم /status <code><order_id></code> للمتابعة

⚠️ <b>ملاحظات:</b>
- تأكد من صحة معرف اللاعب
- تأكد من وجود رصيد كافي
- احفظ رقم الطلب
    ";
    
    sendMessage($telegram, $chatId, $message);
}

/**
 * Unknown Command
 */
else {
    $message = "
❓ <b>أمر غير معروف</b>

💡 استخدم /help لعرض الأوامر
    ";
    
    sendMessage($telegram, $chatId, $message);
}

// Return 200 OK
http_response_code(200);
?>
