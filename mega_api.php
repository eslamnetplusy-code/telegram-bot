<?php

class MegaAPI {
    private $apiUrl = 'https://megatec-center.com/api/rest.php';
    private $username;
    private $apiKey;
    
    public function __construct() {
        $this->username = getenv('MEGA_USERNAME');
        $this->apiKey = getenv('MEGA_API_KEY');
    }
    
    private function getAuthHeader() {
        $credentials = base64_encode("{$this->username}:{$this->apiKey}");
        return "Basic {$credentials}";
    }
    
    public function getBalance() {
        $client = new \GuzzleHttp\Client();
        
        try {
            $response = $client->post($this->apiUrl, [
                'headers' => [
                    'Authorization' => $this->getAuthHeader()
                ],
                'form_params' => [
                    'request' => 'balance'
                ]
            ]);
            
            return json_decode($response->getBody(), true);
        } catch (Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function placeOrder($serviceId, $playerId, $reference) {
        $client = new \GuzzleHttp\Client();
        
        try {
            $response = $client->post($this->apiUrl, [
                'headers' => [
                    'Authorization' => $this->getAuthHeader()
                ],
                'form_params' => [
                    'request' => 'neworder',
                    'service' => $serviceId,
                    'reference' => $reference,
                    'player_id' => $playerId
                ]
            ]);
            
            return json_decode($response->getBody(), true);
        } catch (Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function checkOrderStatus($orderId) {
        $client = new \GuzzleHttp\Client();
        
        try {
            $response = $client->post($this->apiUrl, [
                'headers' => [
                    'Authorization' => $this->getAuthHeader()
                ],
                'form_params' => [
                    'request' => 'orderstatus',
                    'id' => $orderId
                ]
            ]);
            
            return json_decode($response->getBody(), true);
        } catch (Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function getServiceList() {
        $client = new \GuzzleHttp\Client();
        
        try {
            $response = $client->post($this->apiUrl, [
                'headers' => [
                    'Authorization' => $this->getAuthHeader()
                ],
                'form_params' => [
                    'request' => 'servicelist'
                ]
            ]);
            
            return json_decode($response->getBody(), true);
        } catch (Exception $e) => {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }
}
?>
