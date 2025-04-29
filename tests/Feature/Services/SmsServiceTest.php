<?php

namespace Tests\Feature\Services;

use App\Models\User;
use Tests\TestCase;
use Carbon\Carbon;

class SmsServiceTest extends TestCase
{
    public function test_user_can_request_verification_code(): void
    {
        $phone = '+79493316512';

        $response = $this->postJson('/api/login', [
            'phone' => $phone
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'ok' => true,
                'message' => 'Код отправлен на телефон'
            ]);

        $this->assertDatabaseHas('verification_codes', [
            'phone' => $phone
        ]);
    }
}