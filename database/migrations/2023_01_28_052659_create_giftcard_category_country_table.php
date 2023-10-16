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
        Schema::create('giftcard_category_country', function (Blueprint $table) {
            $table->foreignUuid('giftcard_category_id')->constrained();
            $table->foreignUuid('country_id')->constrained();

            $table->primary([
                'giftcard_category_id',
                'country_id',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('giftcard_category_country');
    }
};
