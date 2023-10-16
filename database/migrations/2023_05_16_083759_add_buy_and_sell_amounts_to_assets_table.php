<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->unsignedDecimal('buy_min_amount', 10)->nullable();
            $table->unsignedDecimal('buy_max_amount', 10)->nullable();
            $table->unsignedDecimal('sell_min_amount', 10)->nullable();
            $table->unsignedDecimal('sell_max_amount', 10)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn([
                'buy_min_amount',
                'buy_max_amount',
                'sell_min_amount',
                'sell_max_amount'
            ]);
        });
    }
};
