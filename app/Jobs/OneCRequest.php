<?php

namespace App\Jobs;

use App\Services\OneCClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class OneCRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;

    public function __construct(
        public readonly string $endpoint,
        public readonly array $data,
        public readonly string $method = 'POST'
    ) {}

    public function handle(OneCClient $client): void
    {
        try {
            $response = match($this->method) {
                'GET' => $client->get($this->endpoint),
                'PUT' => $client->put($this->endpoint, $this->data),
                'POST' => $client->post($this->endpoint, $this->data),
                default => throw new \InvalidArgumentException('Unsupported HTTP method')
            };
            
            if (!$response->successful()) {
                Log::error('1C API Error', [
                    'endpoint' => $this->endpoint,
                    'method' => $this->method,
                    'data' => $this->data,
                    'response' => $response->json()
                ]);
                
                throw new \Exception('1C API Error: ' . $response->status());
            }
        } catch (\Exception $e) {
            Log::error('1C Request Failed', [
                'endpoint' => $this->endpoint,
                'method' => $this->method,
                'data' => $this->data,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
}