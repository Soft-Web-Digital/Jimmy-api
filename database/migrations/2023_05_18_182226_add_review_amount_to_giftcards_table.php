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
        Schema::table('giftcards', function (Blueprint $table) {
            $table->unsignedDecimal('review_amount', 15)->after('review_rate')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('giftcards', function (Blueprint $table) {
            $table->dropColumn('review_amount');
        });
    }
};
