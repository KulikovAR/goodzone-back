<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class OneCClient
{
    private string $baseUrl;
    private $request;

    public function __construct()
    {
        $this->baseUrl = config('services.one_c.host');
        $this->request = Http::withToken(config('services.one_c.token'))
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]);
    }

    public function post(string $endpoint, array $data): Response
    {
        return $this->request->post($this->baseUrl . $endpoint, $data);
    }

    public function put(string $endpoint, array $data): Response
    {
        return $this->request->put($this->baseUrl . $endpoint, $data);
    }

    public function get(string $endpoint): Response
    {
        return $this->request->get($this->baseUrl . $endpoint);
    }
}