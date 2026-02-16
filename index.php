<?php
/**
 * Telegram Bot - Mega Center API Integration
 * Developed for Railway Hosting
 */

require 'vendor/autoload.php';
require 'mega_api.php';

use \Telegram\Bot\Api;
use \Telegram\Bot\Commands\Command;

// Initialize Telegram Bot
$telegram = new Api(getenv('8057785864:AAG-TggKI7ILG7JLSEwAuwz6F5WH7ddTne0'));
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
$messageId = $message->getMessageId();

// Store user data temporarily (for multi-step commands)
$userData = [];

/**
 * Send Message Helper Function
 */
function sendMessage($telegram, $chatId, $text, $replyToMessageId = null) {
    return $telegram->sendMessage([
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'HTML',
        'reply_to_message_id' => $replyToMessageId
    ]);
}
/**
 * Command: /start
 */
if ($text === '/start') {
    $welcomeMessage = "
🎮 <b>مرحباً بك في بوت الشحن!</b>

📌 <b>الأوامر المتاحة:</b>
/services - عرض جميع الخدمات المتاحة
/balance - فحص رصيد الحساب
/order - طلب خدمة جديدة
/status - فحص حالة طلب
/help - المساعدة

💡 <b>للبدء:</b>
1. استخدم /services لعرض الخدمات
2. اختر الخدمة المناسبة
3. استخدم /order لطلب الشحن

🔗 <b>الدعم الفني:</b> @YourSupport
    ";
    
    sendMessage($telegram, $chatId, $welcomeMessage);
}

/**
 * Command: /balance - Check Account Balance
 */
elseif ($text === '/balance') {
    $balance = $megaApi->getBalance();
    
    if ($balance['status'] === true) {
        $message = "
💰 <b>رصيد حسابك:</b>

💵 <code>{$balance['balance']} $</code>

⚠️ <b>ملاحظة:</b>
تأكد من وجود رصيد كافي قبل طلب أي خدمة
        ";
    } else {
        $errorCode = $balance['code'] ?? 'Unknown';
        $message = "
❌ <b>خطأ في فحص الرصيد:</b>

🔴 كود الخطأ: <code>{$errorCode}</code>
📝 التفاصيل: {$balance['message']}

⚠️ تأكد من صحة بيانات API في Railway        ";
    }
    
    sendMessage($telegram, $chatId, $message);
}

/**
 * Command: /services - Get Service List
 */
elseif ($text === '/services') {
    $services = $megaApi->getServiceList();
    
    if ($services['status'] === true && isset($services['ServiceList'])) {
        $message = "📋 <b>قائمة الخدمات المتاحة:</b>\n\n";
        
        $count = 0;
        foreach ($services['ServiceList'] as $service) {
            if ($count >= 10) {
                $message .= "\n⚠️ <i>تم عرض أول 10 خدمات فقط</i>";
                break;
            }
            
            $message .= "
🎮 <b>{$service['ServiceName']}</b>
🆔 كود الخدمة: <code>{$service['ServiceApiID']}</code>
💰 السعر: <code>{$service['Price']} $</code>
⏱️ الوقت: {$service['DoTime']}
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
        $message = "❌ <b>فشل في جلب قائمة الخدمات</b>

📝 الخطأ: {$services['message']}
        ";
    }
    
    sendMessage($telegram, $chatId, $message);
}

/** * Command: /order - Place New Order
 * Format: /order <service_id> <player_id>
 */
elseif (strpos($text, '/order') === 0) {
    $parts = explode(' ', trim($text));
    
    if (count($parts) < 3) {
        $message = "
❌ <b>بيانات غير مكتملة!</b>

📝 <b>طريقة الاستخدام:</b>
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
        
        // Generate unique reference (timestamp + user_id + random)
        $reference = time() . '_' . $userId . '_' . rand(1000, 9999);
        
        // Place the order
        $result = $megaApi->placeOrder($serviceId, $playerId, $reference);
        
        if ($result['status'] === true && $result['code'] == 201) {
            $orderId = $result['orderid'];
            $price = $result['price'];
            
            // Store order info for status checking
            $userData[$userId]['last_order'] = $orderId;
            $userData[$userId]['last_reference'] = $reference;
            
            $message = "
✅ <b>تم قبول الطلب بنجاح!</b>

📋 <b>تفاصيل الطلب:</b>
🆔 رقم الطلب: <code>{$orderId}</code>
🔗 الرقم المرجعي: <code>{$reference}</code>
💰 السعر: <code>{$price} $</code>
🎮 معرف اللاعب: <code>{$playerId}</code>
📦 كود الخدمة: <code>{$serviceId}</code>

⏳ <b>حالة الطلب:</b> جاري المعالجة...
💡 <b>لفحص الحالة:</b>
/status {$orderId}
            ";
        } else {
            $errorCode = $result['code'] ?? 'Unknown';
            $errorMsg = $result['message'] ?? 'خطأ غير معروف';
            
            // Error code descriptions based on API documentation
            $errorDesc = '';
            switch ($errorCode) {
                case 401:
                    $errorDesc = "⚠️ بيانات الدخول غير صحيحة";
                    break;
                case 405:
                    $errorDesc = "⚠️ رصيدك غير كافي لتنفيذ هذا الطلب";
                    break;
                case 412:
                    $errorDesc = "⚠️ الرقم المرجعي مكرر، حاول مرة أخرى";
                    break;
                case 414:
                    $errorDesc = "⚠️ بيانات اللاعب غير صحيحة";
                    break;
                case 409:
                    $errorDesc = "⚠️ الخدمة غير متاحة حالياً";
                    break;
                case 415:
                    $errorDesc = "⚠️ النظام تحت الصيانة، حاول لاحقاً";
                    break;
                default:
                    $errorDesc = "⚠️ راجع التفاصيل أدناه";
            }
            
            $message = "
❌ <b>فشل الطلب!</b>

🔴 <b>كود الخطأ:</b> <code>{$errorCode}</code>
📝 <b>التفاصيل:</b> {$errorMsg}
{$errorDesc}

💡 <b>للمساعدة:</b>
- تأكد من صحة بيانات اللاعب
- تأكد من وجود رصيد كافي
- جرب استخدام كود خدمة مختلف
            ";
        }
        
        sendMessage($telegram, $chatId, $message);
    }
}
/**
 * Command: /status - Check Order Status
 * Format: /status <order_id>
 */
elseif (strpos($text, '/status') === 0) {
    $parts = explode(' ', trim($text));
    
    if (count($parts) < 2) {
        $message = "
❌ <b>الرجاء إدخال رقم الطلب!</b>

📝 <b>طريقة الاستخدام:</b>
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
            $msg = $status['msg'] ?? '';
            $result = $status['result'] ?? '';
            
            // Progress status based on API documentation
            $progressText = '';
            $progressEmoji = '';
            
            switch ($progress) {
                case 1:
                    $progressEmoji = '📥';
                    $progressText = 'تم استلام الطلب';
                    break;
                case 2:
                    $progressEmoji = '⚙️';
                    $progressText = 'جاري المعالجة';
                    break;
                case 4:
                    $progressEmoji = '✅';
                    $progressText = 'تم التنفيذ بنجاح';
                    break;
                case 5:
                    $progressEmoji = '❌';
                    $progressText = 'فشل الطلب / تم استرداد الرصيد';
                    break;
                default:
                    $progressEmoji = '⏳';                    $progressText = 'حالة غير معروفة';
            }
            
            $message = "
📋 <b>حالة الطلب:</b>

🆔 رقم الطلب: <code>{$orderId}</code>
{$progressEmoji} <b>الحالة:</b> {$progressText}
📝 <b>التفاصيل:</b> {$msg}
            ";
            
            if ($result && $progress == 4) {
                $message .= "

🎁 <b>نتيجة الشحن:</b>
<code>{$result}</code>
                ";
            }
            
            if ($progress == 2) {
                $message .= "

⏳ <i>جاري المعالجة... يمكنك فحص الحالة لاحقاً</i>
                ";
            }
        } else {
            $errorCode = $status['code'] ?? 'Unknown';
            $message = "
❌ <b>فشل في فحص حالة الطلب</b>

🔴 كود الخطأ: <code>{$errorCode}</code>
📝 التفاصيل: {$status['msg']}

💡 تأكد من صحة رقم الطلب
            ";
        }
        
        sendMessage($telegram, $chatId, $message);
    }
}

/**
 * Command: /help - Show Help
 */
elseif ($text === '/help') {
    $message = "
📚 <b>مركز المساعدة</b>

🎮 <b>الأوامر المتاحة:</b>
/start - البدء واستخدام البوت
/services - عرض جميع الخدمات
/balance - فحص رصيد الحساب
/order - طلب خدمة جديدة
/status - فحص حالة طلب
/help - عرض هذه الرسالة

📝 <b>طريقة الطلب:</b>
1. استخدم /services لعرض الخدمات
2. انسخ كود الخدمة المطلوب
3. استخدم /order <code><service_id></code> <code><player_id></code>
4. انتظر تأكيد الطلب
5. استخدم /status <code><order_id></code> لمتابعة الحالة

⚠️ <b>ملاحظات مهمة:</b>
- تأكد من صحة معرف اللاعب
- تأكد من وجود رصيد كافي
- احفظ رقم الطلب للمتابعة
- الرقم المرجعي فريد لكل عملية

🔗 <b>الدعم الفني:</b> @YourSupport
    ";
    
    sendMessage($telegram, $chatId, $message);
}

/**
 * Command: /test - Test API Connection (Admin Only)
 */
elseif ($text === '/test') {
    // You can add admin check here
    $balance = $megaApi->getBalance();
    $services = $megaApi->getServiceList();
    
    $message = "
🔧 <b>اختبار الاتصال بالـ API</b>

✅ <b>فحص الرصيد:</b>
" . ($balance['status'] ? 'نجح' : 'فشل') . "

✅ <b>فحص الخدمات:</b>
" . ($services['status'] ? 'نجح' : 'فشل') . "

📊 <b>عدد الخدمات:</b> " . (isset($services['ServiceList']) ? count($services['ServiceList']) : 0) . "
    ";
    
    sendMessage($telegram, $chatId, $message);
}

/** * Unknown Command
 */
else {
    $message = "
❓ <b>أمر غير معروف!</b>

💡 <b>استخدم /help لعرض الأوامر المتاحة</b>
    ";
    
    sendMessage($telegram, $chatId, $message);
}

// Return 200 OK for webhook
http_response_code(200);
?>
