<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class SmsService
{
    private $client;
    private $apiUrl;
    private $login;
    private $password;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiUrl = 'https://integrationapi.net/rest';
        $this->login = env('SMS_API_LOGIN');
        $this->password = env('SMS_API_PASSWORD');
    }

    public function getSessionId(): ?string
    {
        try {
            $response = $this->client->get("{$this->apiUrl}/user/sessionid", [
                'query' => [
                    'login' => $this->login,
                    'password' => $this->password,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return $data;
        } catch (RequestException $e) {

            return null;
        }
    }

    public function sendSms(string $sessionId, string $destination, string $message): bool
    {
        try {
            $response = $this->client->post("{$this->apiUrl}/Sms/Send", [
                'query' => [
                    'SessionID' => $sessionId,
                    'SourceAddress' => 'GoodZone',
                    'DestinationAddress' => $destination,
                    'Data' => $message,
                    'Validity' => 1,
                ],
                'headers' => [
                    'Cookie' => "ASP.NET_SessionId={$sessionId}",
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return $data['ok'] ?? false;
        } catch (RequestException $e) {

            return false;
        }
    }
}
