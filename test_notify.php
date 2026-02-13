<?php
include "config.php";

$phone = "777438844";
$status = "جاهزة";
$amount = "5000";
$trx = "TEST123";

$stmt = $conn->prepare("SELECT chat_id FROM users WHERE phone=?");
$stmt->bind_param("s",$phone);
$stmt->execute();
$result = $stmt->get_result();

if($row = $result->fetch_assoc()){

    $chat_id = $row['chat_id'];

    $message = "🧪 تجربة إشعار\n";
    $message .= "🧾 رقم العملية: $trx\n";
    $message .= "💰 المبلغ: $amount\n";
    $message .= "📌 الحالة: $status";

    file_get_contents("https://api.telegram.org/bot8057785864:AAG-TggKI7ILG7JLSEwAuwz6F5WH7ddTne0/sendMessage?chat_id=$chat_id&text=" . urlencode($message));

    echo "تم الإرسال";
}else{
    echo "الرقم غير مربوط بالبوت";
}
?>

