<?php

namespace Tests\Feature\Jobs;

use App\Jobs\OneCRequest;
use App\Services\OneCClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OneCRequestTest extends TestCase
{
    public function test_job_retries_on_failure(): void
    {
        Http::fake([
            '*' => Http::sequence()
                ->push(['error' => 'Server Error'], 500)
                ->push(['status' => 'success'], 200)
        ]);

        $job = new OneCRequest(
            endpoint: '/api/test',
            data: ['test' => 'data'],
            method: 'POST'
        );

        // Simulate job retries
        try {
            $job->handle(app(OneCClient::class));
        } catch (\Exception $e) {
            // First attempt fails
            $job->handle(app(OneCClient::class)); // Second attempt succeeds
        }

        Http::assertSentCount(2); // Verify both attempts were made
    }

    public function test_job_fails_after_max_attempts(): void
    {
        $this->expectException(\Exception::class);

        Http::fake([
            '*' => Http::response(['error' => 'Server Error'], 500)
        ]);

        $job = new OneCRequest(
            endpoint: '/api/test',
            data: ['test' => 'data'],
            method: 'POST'
        );

        $job->handle(app(OneCClient::class));
    }
}