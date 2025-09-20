<?php
namespace App\Services;

use Google\Client as GoogleClient;
use GuzzleHttp\Client as HttpClient;

class FirebaseService
{
    protected $projectId;
    protected $http;
    protected $credentialsPath;

    public function __construct()
    {
        $this->http = new HttpClient();
        $this->credentialsPath = storage_path('app/firebase/firebase-admin-key.json');
        $this->projectId = json_decode(file_get_contents($this->credentialsPath), true)['project_id'];
    }

    private function getAccessToken()
    {
        $googleClient = new GoogleClient();
        $googleClient->setAuthConfig($this->credentialsPath);
        $googleClient->addScope('https://www.googleapis.com/auth/firebase.messaging');
        $token = $googleClient->fetchAccessTokenWithAssertion();
        return $token['access_token'];
    }

    public function sendToToken(string $token, string $title, string $body, array $data = [])
    {
        $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

        $message = [
            "message" => [
                "token" => $token,
                "notification" => [
                    "title" => $title,
                    "body"  => $body,
                ]
            ]
        ];

        // Only add data if it's not empty, and convert to object format
        if (!empty($data)) {
            $message["message"]["data"] = (object) $data;
        }

        try {
            \Log::info('FCM Message Payload:', $message);
            
            $response = $this->http->post($url, [
                'headers' => [
                    'Authorization' => "Bearer " . $this->getAccessToken(),
                    'Content-Type'  => 'application/json',
                ],
                'json' => $message,
            ]);

            $result = json_decode($response->getBody(), true);
            \Log::info('FCM Response Success:', $result);
            
            return $result;
        } catch (\Exception $e) {
            \Log::error('FCM Error:', [
                'message' => $e->getMessage(),
                'payload' => $message
            ]);
            
            return ['error' => $e->getMessage()];
        }
    }
}
