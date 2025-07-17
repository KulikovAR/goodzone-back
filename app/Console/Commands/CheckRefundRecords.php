<?php

namespace App\Console\Commands;

use App\Models\Bonus;
use App\Models\User;
use Illuminate\Console\Command;

class CheckRefundRecords extends Command
{
    protected $signature = 'bonus:check-refunds {phone}';
    protected $description = 'Проверка всех записей возврата для пользователя';

    public function handle()
    {
        $phone = $this->argument('phone');

        $user = User::where('phone', $phone)->first();
        if (!$user) {
            $this->error("Пользователь с телефоном {$phone} не найден");
            return;
        }

        $this->info("=== ВСЕ ЗАПИСИ ВОЗВРАТА ДЛЯ ПОЛЬЗОВАТЕЛЯ ===");
        $this->info("Телефон: {$phone}");
        $this->info("");

        // Ищем все записи возврата
        $refunds = Bonus::where('user_id', $user->id)
            ->where('type', 'refund')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($refunds->isEmpty()) {
            $this->info("Записей возврата не найдено");
            return;
        }

        $this->info("=== ЗАПИСИ ВОЗВРАТА ===");
        $this->table(
            ['ID', 'Дата', 'Сумма', 'ID чека возврата', 'parent_id_sell', 'purchase_amount', 'Статус'],
            $refunds->map(function ($refund) {
                return [
                    $refund->id,
                    $refund->created_at->format('d.m.Y H:i:s'),
                    $refund->amount,
                    $refund->id_sell,
                    $refund->parent_id_sell ?? 'NULL',
                    $refund->purchase_amount ?? '-',
                    $refund->status
                ];
            })->toArray()
        );

        $this->info("");

        // Проверяем записи возврата списанных бонусов
        $debitRefunds = Bonus::where('user_id', $user->id)
            ->where('id_sell', 'like', '%_DEBIT_REFUND')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($debitRefunds->isNotEmpty()) {
            $this->info("=== ЗАПИСИ ВОЗВРАТА СПИСАННЫХ БОНУСОВ ===");
            $this->table(
                ['ID', 'Дата', 'Сумма', 'ID чека', 'parent_id_sell', 'Тип', 'Статус'],
                $debitRefunds->map(function ($refund) {
                    return [
                        $refund->id,
                        $refund->created_at->format('d.m.Y H:i:s'),
                        $refund->amount,
                        $refund->id_sell,
                        $refund->parent_id_sell ?? 'NULL',
                        $refund->type,
                        $refund->status
                    ];
                })->toArray()
            );
        } else {
            $this->info("Записей возврата списанных бонусов не найдено");
        }

        $this->info("");

        // Проверяем все записи с id_sell, содержащим "R1"
        $r1Records = Bonus::where('user_id', $user->id)
            ->where('id_sell', 'like', '%R1%')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($r1Records->isNotEmpty()) {
            $this->info("=== ВСЕ ЗАПИСИ С ID_SELL, СОДЕРЖАЩИМ 'R1' ===");
            $this->table(
                ['ID', 'Дата', 'Сумма', 'Тип', 'ID чека', 'parent_id_sell', 'Статус'],
                $r1Records->map(function ($record) {
                    return [
                        $record->id,
                        $record->created_at->format('d.m.Y H:i:s'),
                        $record->amount,
                        $record->type,
                        $record->id_sell,
                        $record->parent_id_sell ?? 'NULL',
                        $record->status
                    ];
                })->toArray()
            );
        } else {
            $this->info("Записей с id_sell, содержащим 'R1', не найдено");
        }
    }
} 