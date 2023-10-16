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
        Schema::create('user_bank_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('bank_id')->constrained();
            $table->foreignUuid('user_id')->constrained();
            $table->string('account_number');
            $table->string('account_name');
            $table->timestamps();
            $table->softDeletes();

            $table->unique([
                'bank_id',
                'user_id',
                'account_number',
            ], 'ba_bank_user_account_number');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_bank_accounts');
    }
};
