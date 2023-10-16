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
    public function up()
    {
        Schema::table('giftcard_products', function (Blueprint $table) {
            $table->after('sell_max_amount', function (Blueprint $table) {
                $table->unsignedDecimal('buy_min_amount', 10, 2)->nullable();
                $table->unsignedDecimal('buy_max_amount', 10, 2)->nullable();
                $table->string('service_provider')->nullable()->comment('Enum:GiftcardServiceProvider');
                $table->string('service_provider_reference')->nullable();
            });
        });

        Schema::table('giftcard_products', function (Blueprint $table) {
            $table->unsignedDecimal('sell_rate', 10, 2)->nullable()->change();
        });

        Schema::table('giftcard_products', function (Blueprint $table) {
            $table->unsignedDecimal('sell_min_amount', 10, 2)->nullable()->change();
        });

        Schema::table('giftcard_products', function (Blueprint $table) {
            $table->unsignedDecimal('sell_max_amount', 10, 2)->nullable()->change();
        });

        Schema::table('giftcard_products', function (Blueprint $table) {
            $table->dropForeign(['giftcard_category_id']);
            $table->dropForeign(['country_id']);
            $table->dropForeign(['currency_id']);
        });

        Schema::table('giftcard_products', function (Blueprint $table) {
            $table->dropUnique('gp_gc_c_c_name');
        });

        Schema::table('giftcard_products', function (Blueprint $table) {
            $table->foreign('giftcard_category_id')->on('giftcard_categories')->references('id');
            $table->foreign('country_id')->on('countries')->references('id');
            $table->foreign('currency_id')->on('currencies')->references('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('giftcard_products', function (Blueprint $table) {
            $table->dropColumn([
                'buy_min_amount',
                'buy_max_amount',
                'service_provider',
                'service_provider_reference',
            ]);
        });
    }
};
