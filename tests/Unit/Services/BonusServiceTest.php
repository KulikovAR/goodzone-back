<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\Bonus;
use App\Services\BonusService;
use App\Services\ExpoNotificationService;
use App\Enums\NotificationType;
use Carbon\Carbon;
use Mockery;
use Mockery\MockInterface;
use Mockery\LegacyMockInterface;

class BonusServiceTest extends TestCase
{
    private ExpoNotificationService|MockInterface|LegacyMockInterface $mockPushService;
    private BonusService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockPushService = Mockery::mock(ExpoNotificationService::class);
        $this->service = new BonusService($this->mockPushService);
    }

    public function test_credit_bonus_creates_bonus_and_sends_notification()
    {
        $user = User::factory()->create([
            'phone' => fake()->numerify('7##########'),
        ]);

        $this->mockPushService->shouldReceive('send')
            ->once()
            ->with(
                $user,
                NotificationType::BONUS_CREDIT,
                [
                    'amount' => 100,
                    'purchase_amount' => 1000,
                    'phone' => $user->phone
                ]
            );

        $bonus = $this->service->creditBonus($user, 100, 1000);

        $this->assertDatabaseHas('bonuses', [
            'user_id' => $user->id,
            'amount' => 100,
            'purchase_amount' => 1000,
            'type' => 'regular'
        ]);
    }

    public function test_debit_bonus_throws_exception_when_insufficient_balance()
    {
        $user = User::factory()->create();
        
        // First set the mock expectation
        $this->mockPushService->shouldReceive('send')->never();
        
        // Then expect exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Недостаточно бонусов');

        $this->service->debitBonus($user, 100);
    }

    public function test_debit_bonus_processes_and_sends_notification()
    {
        $user = User::factory()->create();
        
        // Создаем бонус и устанавливаем баланс
        $user->bonuses()->create([
            'amount' => 100,
            'type' => 'regular'
        ]);
        $user->bonus_amount = 100;
        $user->save();

        $this->mockPushService->shouldReceive('send')
            ->once()
            ->with(
                $user,
                NotificationType::BONUS_DEBIT,
                [
                    'debit_amount' => 50,
                    'remaining_bonus' => '50.00',
                    'phone' => $user->phone
                ]
            );

        $this->service->debitBonus($user, 50);

        $this->assertDatabaseHas('bonuses', [
            'user_id' => $user->id,
            'amount' => -50,
            'type' => 'regular'
        ]);

        $user->refresh();
        $this->assertEquals(50, $user->bonus_amount);
    }

    public function test_debit_bonus_uses_expiring_bonuses_first()
    {
        $user = User::factory()->create();
        $earlierExpiry = Carbon::now()->addDays(10);
        $laterExpiry = Carbon::now()->addDays(20);

        // Создаем бонусы с разными датами истечения
        Bonus::factory()->create([
            'user_id' => $user->id,
            'amount' => 50,
            'type' => 'promotional',
            'expires_at' => $laterExpiry
        ]);

        Bonus::factory()->create([
            'user_id' => $user->id,
            'amount' => 50,
            'type' => 'promotional',
            'expires_at' => $earlierExpiry
        ]);

        // Устанавливаем общий баланс
        $user->bonus_amount = 100;
        $user->save();

        $this->mockPushService->shouldReceive('send')
            ->once()
            ->with(
                $user,
                NotificationType::BONUS_DEBIT,
                [
                    'debit_amount' => 60,
                    'remaining_bonus' => '40.00',
                    'phone' => $user->phone
                ]
            );

        $this->service->debitBonus($user, 60);

        $this->assertDatabaseHas('bonuses', [
            'user_id' => $user->id,
            'amount' => -60,
            'type' => 'regular'
        ]);

        $user->refresh();
        $this->assertEquals(40, $user->bonus_amount);
    }

    public function test_credit_promotional_bonus_creates_bonus_and_sends_notification()
    {
        $user = User::factory()->create();
        $expiryDate = Carbon::now()->addDays(30);

        $this->mockPushService->shouldReceive('send')
            ->once()
            ->with(
                $user,
                NotificationType::BONUS_PROMOTION,
                [
                    'bonus_amount' => 100,
                    'expiry_date' => $expiryDate->format('d.m.Y H:i'),
                    'phone' => $user->phone
                ]
            );

        $bonus = $this->service->creditPromotionalBonus($user, 100, $expiryDate);

        $this->assertDatabaseHas('bonuses', [
            'user_id' => $user->id,
            'amount' => 100,
            'type' => 'promotional',
            'expires_at' => $expiryDate
        ]);
    }
}