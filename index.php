function sendOrder($service_id, $player_id) {

    $username = "u_3862970154";
    $password = "Fekri-738911634";

    $postData = [
        "request"   => "neworder",
        "service"   => $service_id,
        "reference" => time() . rand(100,999),
        "player_id" => $player_id
    ];

    $ch = curl_init("https://megatec-center.com/api/rest.php");

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_USERPWD => "$username:$password",
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => false
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return "CURL ERROR: $error";
    }

    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return "HTTP CODE: $httpcode\n\n$response";
}
