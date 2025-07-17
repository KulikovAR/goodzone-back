<?php

namespace App\Services\Dadata;

use Illuminate\Support\Facades\Http;

class DadataClient
{
    public $client;

    public function __construct()
    {
        $this->client = Http::withHeaders(
            [
                'Authorization' => 'Token '.config('dadata.token'),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]
        );
    }
}
