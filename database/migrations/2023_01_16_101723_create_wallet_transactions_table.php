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
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuidMorphs('user');
            $table->uuidMorphs('causer');
            $table->string('service')->comment('Enum:WalletServiceType');
            $table->string('type')->comment('Enum:WalletTransactionType');
            $table->unsignedDecimal('amount', 15, 2);
            $table->mediumText('summary');
            $table->mediumText('admin_note')->nullable();
            $table->string('receipt')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
