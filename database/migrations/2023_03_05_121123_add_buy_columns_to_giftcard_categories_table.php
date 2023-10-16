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
        Schema::table('giftcard_categories', function (Blueprint $table) {
            $table->text('purchase_term')->nullable()->after('sale_term');
            $table->string('service_provider')->nullable()->comment('Enum:GiftcardServiceProvider')->after('icon');
            $table->timestamp('purchase_activated_at')->nullable()->after('activated_at');
        });

        Schema::table('giftcard_categories', function (Blueprint $table) {
            $table->renameColumn('activated_at', 'sale_activated_at');

            $table->dropUnique(['name']);

            $table->unique([
                'name',
                'service_provider',
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
        Schema::table('giftcard_categories', function (Blueprint $table) {
            $table->dropUnique([
                'name',
                'service_provider',
            ]);

            $table->unique('name');

            $table->dropColumn([
                'purchase_term',
                'service_provider',
                'purchase_activated_at',
            ]);
        });
    }
};
