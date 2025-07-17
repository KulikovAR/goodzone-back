<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Tests\TestCase;
use App\Services\ExpoNotificationService;
use App\Enums\NotificationType;
use App\Services\BonusService;

class OneCIntegrationTest extends TestCase
{
    protected $mockPushService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockPushService = $this->createMock(ExpoNotificationService::class);
        $this->app->instance(ExpoNotificationService::class, $this->mockPushService);

        $this->app->instance(BonusService::class, new BonusService($this->mockPushService));
    }

    public function test_1c_user_can_access_protected_endpoints()
    {
        $regularUser = User::factory()->create(['phone' => '+7' . fake()->unique()->numerify('##########')]);
        $oneCUser = User::factory()->oneC()->create();
        $oneCToken = $oneCUser->createToken('test-token')->plainTextToken;

        $this->mockPushService->expects($this->once())
            ->method('send')
            ->with(
                $this->isInstanceOf(User::class),
                NotificationType::BONUS_CREDIT,
                $this->anything()
            );

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $oneCToken
        ])->postJson('/api/bonus/credit', [[
            'phone' => $regularUser->phone,
            'purchase_amount' => 1000,
            'id_sell' => 'TEST_RECEIPT_' . time(),
            'timestamp' => now()->toDateTimeString()
        ]]);

        $response->assertOk();
    }

    public function test_regular_user_cannot_access_protected_endpoints()
    {
        $regularUser = User::factory()->create(['phone' => '+7' . fake()->unique()->numerify('##########')]);
        $regularToken = $regularUser->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $regularToken
        ])->postJson('/api/bonus/credit', [[
            'phone' => $regularUser->phone,
            'purchase_amount' => 1000,
            'id_sell' => 'TEST_RECEIPT_' . time(),
            'timestamp' => now()->toDateTimeString()
        ]]);

        $response->assertStatus(403);
    }

    public function test_create_1c_user_command()
    {
        // Удаляем существующего 1С пользователя, если есть
        User::where('role', UserRole::ONE_C)->delete();

        // Проверяем создание нового пользователя
        $this->artisan('user:create-1c')
            ->expectsOutputToContain('1C User Token:')
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'role' => UserRole::ONE_C->value,
            'name' => '1C Integration'
        ]);

        // Запоминаем ID первого пользователя
        $firstUserId = User::where('role', UserRole::ONE_C)->first()->id;
        
        // Проверяем, что повторный запуск не создает нового пользователя
        $this->artisan('user:create-1c')
            ->expectsConfirmation('Do you want to create a new token for existing user?', 'no')
            ->assertExitCode(0);

        $currentUser = User::where('role', UserRole::ONE_C)->first();
        $this->assertEquals($firstUserId, $currentUser->id);
        $this->assertEquals(1, User::where('role', UserRole::ONE_C)->count());
    }
}