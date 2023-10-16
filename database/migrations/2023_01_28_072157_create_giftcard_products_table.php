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
        Schema::create('giftcard_products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('giftcard_category_id')->constrained();
            $table->foreignUuid('country_id')->constrained();
            $table->foreignUuid('currency_id')->constrained();
            $table->string('name');
            $table->unsignedDecimal('sell_rate', 10, 2);
            $table->unsignedDecimal('sell_min_amount', 10, 2);
            $table->unsignedDecimal('sell_max_amount', 10, 2);
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique([
                'giftcard_category_id',
                'country_id',
                'currency_id',
                'name',
            ], 'gp_gc_c_c_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('giftcard_products');
    }
};
