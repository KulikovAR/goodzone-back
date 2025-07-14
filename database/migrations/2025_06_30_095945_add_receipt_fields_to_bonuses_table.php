<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bonuses', function (Blueprint $table) {
            $table->string('id_sell')->nullable()->after('id')->comment('ID чека (продажи или возврата)');
            $table->string('parent_id_sell')->nullable()->after('id_sell')->comment('ID родительского чека продажи (для возвратов)');
            
            // Добавляем индексы для быстрого поиска
            $table->index(['user_id', 'id_sell']);
            $table->index(['user_id', 'parent_id_sell']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bonuses', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'id_sell']);
            $table->dropIndex(['user_id', 'parent_id_sell']);
            $table->dropColumn(['id_sell', 'parent_id_sell']);
        });
    }
};
