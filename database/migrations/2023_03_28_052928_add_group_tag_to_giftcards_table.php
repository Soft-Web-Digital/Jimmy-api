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
        Schema::table('giftcards', function (Blueprint $table) {
            $table->after('user_id', function (Blueprint $table) {
                $table->foreignUuid('parent_id')->nullable()->constrained('giftcards', 'id')->cascadeOnDelete();
                $table->string('group_tag')->nullable()->index();
            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('giftcards', function (Blueprint $table) {
            $table->dropColumn('group_tag');
            $table->dropConstrainedForeignId('parent_id');
        });
    }
};
