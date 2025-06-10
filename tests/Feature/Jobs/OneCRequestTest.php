<?php

namespace Tests\Feature\Jobs;

use App\Jobs\OneCRequest;
use App\Services\OneCClient;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OneCRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Mock config values for tests
        Config::set('services.one_c.host', 'https://test-1c.example.com');
        Config::set('services.one_c.token', 'test-token');
        
        // Set environment to production for testing
        $this->app['env'] = 'production';
    }

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

        try {
            $job->handle(app(OneCClient::class));
        } catch (\Exception $e) {
            // First attempt fails as expected
            $this->assertEquals('1C API Error: 500', $e->getMessage());
        }

        Http::assertSentCount(1);

        // Second attempt should succeed
        $job->handle(app(OneCClient::class));

        Http::assertSentCount(2);
    }

    public function test_job_fails_after_max_attempts(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('1C API Error: 500');

        Http::fake([
            '*' => Http::response(['error' => 'Server Error'], 500)
        ]);

        $job = new OneCRequest(
            endpoint: '/api/test',
            data: ['test' => 'data'],
            method: 'POST'
        );

        // Simulate all retry attempts
        for ($i = 0; $i < $job->tries; $i++) {
            try {
                $job->handle(app(OneCClient::class));
            } catch (\Exception $e) {
                if ($i === $job->tries - 1) {
                    throw $e;
                }
                $job->failed($e);
            }
        }
    }
}