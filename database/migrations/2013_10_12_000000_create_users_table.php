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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('phone')->unique();
            $table->string('device_token')->nullable()->unique();
            $table->string('email')->unique()->nullable();
            $table->timestamp('code_send_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->boolean('come_from_app')->default(false);
            $table->string('password')->nullable();
            $table->string('gender')->nullable();
            $table->string('city')->nullable();
            $table->string('role')->default('user');
            $table->decimal('bonus_amount', 10, 2)->default(0);
            $table->decimal('total_purchases', 10, 2)->default(0);
            $table->decimal('purchase_amount', 10, 2)->default(0);
            $table->boolean('profile_completed_bonus')->default(false);
            $table->softDeletes();
            $table->timestamps();
            $table->rememberToken();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
