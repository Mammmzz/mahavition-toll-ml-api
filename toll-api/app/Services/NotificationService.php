<?php

namespace App\Services;

use GuzzleHttp\Client;

class NotificationService
{
    protected $client;
    protected $serverKey;

    public function __construct()
    {
        $this->client = new Client();
        $this->serverKey = env('FCM_SERVER_KEY');
    }

    public function sendToToken(string $token, string $title, string $body, array $data = [])
    {
        $response = $this->client->post('https://fcm.googleapis.com/fcm/send', [
            'headers' => [
                'Authorization' => 'key=' . $this->serverKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'to' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $data,
            ],
        ]);

        return json_decode($response->getBody(), true);
    }
}
