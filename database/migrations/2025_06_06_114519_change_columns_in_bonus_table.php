<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bonuses', function (Blueprint $table) {
            $table->dropColumn('used');
            $table->dropColumn('service');

            $table->string('status')->default(false); // show-and-calc, show-not-calc, calc-not-show
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bonuses', function (Blueprint $table) {
            $table->boolean('used')->default(false);
            $table->boolean('service')->default(false);

            $table->dropColumn('status');

        });
    }
};
