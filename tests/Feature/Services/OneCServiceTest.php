<?php

namespace Tests\Feature\Services;  // Changed from Tests\Unit\Services

use App\Jobs\OneCRequest;
use App\Models\User;
use App\Services\OneCService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OneCServiceTest extends TestCase
{
    private OneCService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(OneCService::class);
        $this->user = User::factory()->create([
            'phone' => fake()->numerify('7##########'),
        ]);

        Queue::fake();
    }

    public function test_send_register(): void
    {
        Http::fake([
            config('services.one_c.host') . config('one-c.routes.register') => Http::response([
                'status' => 'success',
                'message' => 'Регистрация успешна'
            ], 200)
        ]);

        // Set come_from_app value for testing
        $this->user->come_from_app = false;
        $this->user->save();

        $this->service->sendRegister($this->user);

        Queue::assertPushed(OneCRequest::class, function ($job) {
            return $job->endpoint === config('one-c.routes.register')
                && $job->method === 'POST'
                && $job->data === [
                    'phone' => $this->user->phone,
                    'already_in_app' => $this->user->come_from_app
                ];
        });
    }

    public function test_send_user(): void
    {
        $endpoint = str_replace('{phone}', $this->user->phone, config('one-c.routes.user'));

        $this->service->sendUser($this->user);

        Queue::assertPushed(OneCRequest::class, function ($job) use ($endpoint) {
            return $job->endpoint === $endpoint
                && $job->method === 'GET'
                && empty($job->data);
        });
    }

    public function test_update_user(): void
    {
        $endpoint = str_replace('{phone}', $this->user->phone, config('one-c.routes.user'));

        $this->service->updateUser($this->user);

        Queue::assertPushed(OneCRequest::class, function ($job) use ($endpoint) {
            return $job->endpoint === $endpoint
                && $job->method === 'PUT'
                && $job->data === [
                    'name' => $this->user->name,
                    'gender' => $this->user->gender,
                    'city' => $this->user->city,
                    'email' => $this->user->email,
                    'birthday' => $this->user->birthday?->format('Y-m-d'),
                    'children' => $this->user->children,
                    'marital_status' => $this->user->marital_status,
                ];
        });
    }
}