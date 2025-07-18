<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('device_token');
            $table->string('platform')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'device_token']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_device_tokens');
    }
}; 