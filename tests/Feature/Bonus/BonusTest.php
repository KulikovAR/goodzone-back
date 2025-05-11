<?php

namespace Tests\Feature\Bonus;

use App\Models\User;
use App\Services\BonusService;
use App\Services\PushNotificationService;
use App\Enums\NotificationType;
use Tests\TestCase;
use Carbon\Carbon;
use Mockery;

class BonusTest extends TestCase
{
    private $mockPushService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockPushService = Mockery::mock(PushNotificationService::class);
        $this->app->instance(PushNotificationService::class, $this->mockPushService);

        $this->app->instance(BonusService::class, new BonusService($this->mockPushService));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_user_can_receive_bonus_for_purchase()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $this->mockPushService->shouldReceive('send')
            ->once()
            ->with(
                Mockery::type(User::class),
                NotificationType::BONUS_CREDIT,
                [
                    'amount' => 50,
                    'purchase_amount' => 1000,
                    'phone' => $user->phone
                ]
            );

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/bonus/credit', [
            'phone' => $user->phone,
            'purchase_amount' => 1000,
            'bonus_amount' => 50
        ]);

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'message' => 'Бонусы начислены'
            ]);

        $this->assertDatabaseHas('bonuses', [
            'user_id' => $user->id,
            'amount' => 50,
            'purchase_amount' => 1000,
            'type' => 'regular'
        ]);

        $user->refresh();
        $this->assertEquals(50, $user->bonus_amount);
    }

    public function test_user_can_debit_bonus()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $user->bonus_amount = 100;
        $user->save();

        // Создаем бонус
        $user->bonuses()->create([
            'amount' => 100,
            'purchase_amount' => 1000,
            'type' => 'regular'
        ]);

        $user->refresh();
        $this->assertEquals(100, $user->bonus_amount);

        $this->mockPushService->shouldReceive('send')
            ->once()
            ->with(
                Mockery::type(User::class),
                NotificationType::BONUS_DEBIT,
                [
                    'debit_amount' => 30,
                    'phone' => $user->phone
                ]
            );

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/bonus/debit', [
            'phone' => $user->phone,
            'debit_amount' => 30
        ]);

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'message' => 'Бонусы списаны'
            ]);

        $this->assertDatabaseHas('bonuses', [
            'user_id' => $user->id,
            'amount' => -30,
            'type' => 'regular'
        ]);

        $user->refresh();
        $this->assertEquals(70, $user->bonus_amount);
    }

    public function test_user_cannot_debit_more_than_available()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Создаем бонус
        $user->bonuses()->create([
            'amount' => 50,
            'purchase_amount' => 1000,
            'type' => 'regular'
        ]);

        $user->bonus_amount = 50;
        $user->save();

        $user->refresh();
        $this->assertEquals(50, $user->bonus_amount);

        $this->mockPushService->shouldReceive('send')->never();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/bonus/debit', [
            'phone' => $user->phone,
            'debit_amount' => 100,
            'purchase_amount' => 50,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'ok' => false,
                'message' => 'Недостаточно бонусов'
            ]);

        $user->refresh();
        $this->assertEquals(50, $user->bonus_amount);
    }

    public function test_user_can_receive_promotional_bonus()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $expiryDate = now()->addYear();

        $this->mockPushService->shouldReceive('send')
            ->once()
            ->with(
                Mockery::type(User::class),
                NotificationType::BONUS_PROMOTION,
                [
                    'bonus_amount' => 100,
                    'expiry_date' => $expiryDate->format('Y-m-d\TH:i:s'),
                    'phone' => $user->phone
                ]
            );

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/bonus/promotion', [
            'phone' => $user->phone,
            'bonus_amount' => 100,
            'expiry_date' => $expiryDate->format('Y-m-d\TH:i:s')
        ]);

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'message' => 'Акционные бонусы начислены'
            ]);

        $this->assertDatabaseHas('bonuses', [
            'user_id' => $user->id,
            'amount' => 100,
            'type' => 'promotional',
            'expires_at' => $expiryDate
        ]);

        $user->refresh();
        $this->assertEquals(100, $user->bonus_amount);
    }

    public function test_unauthorized_user_cannot_access_bonus_endpoints()
    {
        $response = $this->postJson('/api/bonus/credit', [
            'phone' => '+79991234567',
            'purchase_amount' => 1000,
            'bonus_amount' => 50
        ]);

        $response->assertUnauthorized();
    }
}
