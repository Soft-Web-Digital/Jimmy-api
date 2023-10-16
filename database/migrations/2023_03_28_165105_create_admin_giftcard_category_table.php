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
        Schema::create('admin_giftcard_category', function (Blueprint $table) {
            $table->foreignUuid('admin_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('giftcard_category_id')->constrained()->cascadeOnDelete();

            $table->primary([
                'admin_id',
                'giftcard_category_id',
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
        Schema::dropIfExists('admin_giftcard_category');
    }
};
