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
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('country_id')->constrained();
            $table->string('firstname');
            $table->string('lastname');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('username')->unique();
            $table->string('avatar')->nullable();
            $table->string('phone_number')->nullable();
            $table->unsignedDecimal('wallet_balance', 15, 2)->default(0);
            $table->timestamp('two_fa_activated_at')->nullable();
            $table->string('transaction_pin')->nullable();
            $table->boolean('transaction_pin_set')->default(false);
            $table->timestamp('transaction_pin_activated_at')->nullable();
            $table->timestamp('blocked_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->mediumText('deleted_reason')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};
