<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class OneCClient
{
    private string $baseUrl;
    private string $token;

    public function __construct()
    {
        $this->baseUrl = config('services.one_c.url');
        $this->token = config('services.one_c.token');
    }

    public function post(string $endpoint, array $data): Response
    {
        return Http::withToken($this->token)
            ->post($this->baseUrl . $endpoint, $data);
    }

    public function put(string $endpoint, array $data): Response
    {
        return Http::withToken($this->token)
            ->put($this->baseUrl . $endpoint, $data);
    }

    public function get(string $endpoint): Response
    {
        return Http::withToken($this->token)
            ->get($this->baseUrl . $endpoint);
    }
}