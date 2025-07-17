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
use Illuminate\Foundation\Testing\DatabaseMigrations;

class BonusServiceTest extends TestCase
{
    use DatabaseMigrations;

    private ExpoNotificationService|MockInterface|LegacyMockInterface $mockPushService;
    private BonusService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockPushService = Mockery::mock(ExpoNotificationService::class);
        $this->service = new BonusService($this->mockPushService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
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
                Mockery::any()
            );

        $bonus = $this->service->creditBonus($user, 1000, 'TEST_RECEIPT_' . time());

        $this->assertDatabaseHas('bonuses', [
            'user_id' => $user->id,
            'amount' => 50, // 5% от 1000
            'purchase_amount' => 1000,
            'type' => 'regular'
        ]);
    }

    public function test_debit_bonus_throws_exception_when_insufficient_balance()
    {
        $user = User::factory()->create();
        $this->mockPushService->shouldReceive('send')->never();
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Недостаточно бонусов');
        $this->service->debitBonus($user, 100);
    }

    public function test_debit_bonus_processes_and_sends_notification()
    {
        $user = User::factory()->create();
        
        // Создаем исходный чек покупки
        $user->bonuses()->create([
            'amount' => 100,
            'type' => 'regular',
            'status' => 'show-and-calc',
            'id_sell' => 'PARENT_RECEIPT_123',
            'purchase_amount' => 1000,
        ]);
        $user->bonus_amount = 100;
        $user->save();
        
        $this->mockPushService->shouldReceive('send')
            ->once()
            ->with(
                $user,
                NotificationType::BONUS_DEBIT,
                Mockery::any()
            );
        
        $this->service->debitBonus($user, 50, 'TEST_DEBIT_' . time(), 'PARENT_RECEIPT_123');
        
        $this->assertDatabaseHas('bonuses', [
            'user_id' => $user->id,
            'amount' => -50,
            'type' => 'regular',
            'parent_id_sell' => 'PARENT_RECEIPT_123'
        ]);
        
        $user->refresh();
        $this->assertEquals(50, $user->bonus_amount);
    }

    public function test_debit_bonus_uses_promotional_bonuses_first()
    {
        $user = User::factory()->create();
        $earlierExpiry = Carbon::now()->addDays(10);
        $laterExpiry = Carbon::now()->addDays(20);
        
        // Создаем исходный чек покупки
        $user->bonuses()->create([
            'amount' => 100,
            'type' => 'regular',
            'status' => 'show-and-calc',
            'id_sell' => 'PARENT_RECEIPT_123',
            'purchase_amount' => 1000,
        ]);
        
        Bonus::factory()->create([
            'user_id' => $user->id,
            'amount' => 50,
            'type' => 'promotional',
            'expires_at' => $laterExpiry,
            'status' => 'show-and-calc'
        ]);
        Bonus::factory()->create([
            'user_id' => $user->id,
            'amount' => 50,
            'type' => 'promotional',
            'expires_at' => $earlierExpiry,
            'status' => 'show-and-calc'
        ]);
        
        $user->bonus_amount = 200;
        $user->save();
        
        $this->mockPushService->shouldReceive('send')
            ->once()
            ->with(
                $user,
                NotificationType::BONUS_DEBIT,
                Mockery::any()
            );
        
        $this->service->debitBonus($user, 120, 'TEST_DEBIT_' . time(), 'PARENT_RECEIPT_123');
        
        $this->assertDatabaseHas('bonuses', [
            'user_id' => $user->id,
            'amount' => -120,
            'type' => 'regular',
            'status' => 'show-not-calc',
            'parent_id_sell' => 'PARENT_RECEIPT_123'
        ]);
        $this->assertDatabaseHas('bonuses', [
            'user_id' => $user->id,
            'amount' => -20,
            'type' => 'regular',
            'status' => 'calc-not-show',
            'parent_id_sell' => 'PARENT_RECEIPT_123'
        ]);
        
        $user->refresh();
        $this->assertEquals(80, $user->bonus_amount);
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
                Mockery::any()
            );
        $bonus = $this->service->creditPromotionalBonus($user, 100, $expiryDate);
        $this->assertDatabaseHas('bonuses', [
            'user_id' => $user->id,
            'amount' => 100,
            'type' => 'promotional',
            'expires_at' => $expiryDate
        ]);
    }

    // НОВЫЕ ТЕСТЫ ДЛЯ ЛОГИКИ ВОЗВРАТА ТОВАРА

    public function test_refund_bonus_by_receipt_returns_regular_bonuses_only()
    {
        $user = User::factory()->create();
        $parentReceiptId = 'PARENT_RECEIPT_123';
        $refundReceiptId = 'REFUND_RECEIPT_456';
        $refundAmount = 500;
        $user->bonuses()->create([
            'amount' => 50,
            'purchase_amount' => 1000,
            'type' => 'regular',
            'status' => 'show-and-calc',
            'id_sell' => $parentReceiptId
        ]);
        $user->bonuses()->create([
            'amount' => 100,
            'type' => 'promotional',
            'status' => 'show-and-calc',
            'expires_at' => Carbon::now()->addDays(30)
        ]);
        $user->bonus_amount = 150;
        $user->purchase_amount = 1000;
        $user->save();
        $this->mockPushService->shouldReceive('send')
            ->once()
            ->with($user, NotificationType::BONUS_DEBIT, Mockery::any());
        $result = $this->service->refundBonusByReceipt($user, $refundReceiptId, $parentReceiptId, $refundAmount);
        $this->assertDatabaseHas('bonuses', [
            'user_id' => $user->id,
            'amount' => -25,
            'type' => 'refund',
            'purchase_amount' => 500,
            'id_sell' => $refundReceiptId,
            'parent_id_sell' => $parentReceiptId
        ]);
        $this->assertDatabaseHas('bonuses', [
            'user_id' => $user->id,
            'amount' => 100,
            'type' => 'promotional'
        ]);
        $user->refresh();
        $this->assertEquals(125, $user->bonus_amount);
        $this->assertEquals(500, $user->purchase_amount);
        $this->assertEquals(-25, $result['refund_bonus']->amount);
        $this->assertEquals(0, $result['returned_debit_amount']);
    }

    public function test_refund_bonus_by_receipt_returns_proportional_debited_bonuses()
    {
        $user = User::factory()->create();
        $parentReceiptId = 'PARENT_RECEIPT_123';
        $refundReceiptId = 'REFUND_RECEIPT_456';
        $refundAmount = 500;
        $user->bonuses()->create([
            'amount' => 50,
            'purchase_amount' => 1000,
            'type' => 'regular',
            'status' => 'show-and-calc',
            'id_sell' => $parentReceiptId
        ]);
        $user->bonuses()->create([
            'amount' => -25,
            'type' => 'regular',
            'status' => 'calc-not-show',
            'parent_id_sell' => $parentReceiptId
        ]);
        $user->bonus_amount = 25;
        $user->purchase_amount = 1000;
        $user->save();
        $this->mockPushService->shouldReceive('send')
            ->once()
            ->with($user, NotificationType::BONUS_DEBIT, Mockery::any());
        $result = $this->service->refundBonusByReceipt($user, $refundReceiptId, $parentReceiptId, $refundAmount);
        $this->assertDatabaseHas('bonuses', [
            'user_id' => $user->id,
            'amount' => -25,
            'type' => 'refund',
            'purchase_amount' => 500,
            'id_sell' => $refundReceiptId,
            'parent_id_sell' => $parentReceiptId
        ]);
        $this->assertDatabaseHas('bonuses', [
            'user_id' => $user->id,
            'amount' => 12.5,
            'type' => 'regular',
            'status' => 'show-and-calc',
            'id_sell' => $refundReceiptId . '_DEBIT_REFUND',
            'parent_id_sell' => $parentReceiptId
        ]);
        $user->refresh();
        $this->assertEquals(12, $user->bonus_amount);
        $this->assertEquals(500, $user->purchase_amount);
        $this->assertEquals(-25, $result['refund_bonus']->amount);
        $this->assertEquals(12.5, $result['returned_debit_amount']);
    }

    public function test_refund_bonus_by_receipt_prevents_duplicate_processing()
    {
        $user = User::factory()->create();
        $parentReceiptId = 'PARENT_RECEIPT_123';
        $refundReceiptId = 'REFUND_RECEIPT_456';
        $refundAmount = 500;
        $user->bonuses()->create([
            'amount' => 50,
            'purchase_amount' => 1000,
            'type' => 'regular',
            'status' => 'show-and-calc',
            'id_sell' => $parentReceiptId
        ]);
        $existingRefund = $user->bonuses()->create([
            'amount' => -25,
            'type' => 'refund',
            'purchase_amount' => 500,
            'id_sell' => $refundReceiptId,
            'parent_id_sell' => $parentReceiptId
        ]);
        $user->bonus_amount = 25;
        $user->purchase_amount = 500;
        $user->save();
        $this->mockPushService->shouldReceive('send')->never();
        $result = $this->service->refundBonusByReceipt($user, $refundReceiptId, $parentReceiptId, $refundAmount);
        $this->assertEquals($existingRefund->id, $result['refund_bonus']->id);
        $this->assertEquals(0, $result['returned_debit_amount']);
    }

    public function test_refund_bonus_by_receipt_throws_exception_for_invalid_parent_receipt()
    {
        $user = User::factory()->create();
        $parentReceiptId = 'NONEXISTENT_RECEIPT';
        $refundReceiptId = 'REFUND_RECEIPT_456';
        $refundAmount = 500;
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Исходный чек продажи с ID {$parentReceiptId} не найден для данного пользователя");
        $this->service->refundBonusByReceipt($user, $refundReceiptId, $parentReceiptId, $refundAmount);
    }

    public function test_refund_bonus_by_receipt_throws_exception_for_excessive_refund()
    {
        $user = User::factory()->create();
        $parentReceiptId = 'PARENT_RECEIPT_123';
        $refundReceiptId = 'REFUND_RECEIPT_456';
        $refundAmount = 600;
        $user->bonuses()->create([
            'amount' => 50,
            'purchase_amount' => 1000,
            'type' => 'regular',
            'status' => 'show-and-calc',
            'id_sell' => $parentReceiptId
        ]);
        $user->bonuses()->create([
            'amount' => -25,
            'type' => 'refund',
            'purchase_amount' => 500,
            'id_sell' => 'PREVIOUS_REFUND',
            'parent_id_sell' => $parentReceiptId
        ]);
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Сумма возврата превышает сумму исходной покупки");
        $this->service->refundBonusByReceipt($user, $refundReceiptId, $parentReceiptId, $refundAmount);
    }

    public function test_negative_balance_is_preserved_after_refund()
    {
        $user = User::factory()->create();
        $user->bonuses()->create([
            'amount' => -150,
            'type' => 'refund',
            'status' => 'show-and-calc'
        ]);
        $this->service->recalculateUserBonus($user);
        $user->refresh();
        $this->assertEquals(-150, $user->bonus_amount);
    }

    public function test_cannot_debit_from_negative_balance()
    {
        $user = User::factory()->create();
        $user->bonus_amount = -50;
        $user->save();
        $this->mockPushService->shouldReceive('send')->never();
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Недостаточно бонусов');
        $this->service->debitBonus($user, 10, 'TEST_DEBIT_' . time());
    }
}