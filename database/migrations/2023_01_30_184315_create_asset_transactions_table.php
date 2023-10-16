<?php

use App\Enums\AssetTransactionStatus;
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
        Schema::create('asset_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('network_id')->constrained();
            $table->foreignUuid('asset_id')->constrained();
            $table->foreignUuid('user_id')->constrained();
            $table->foreignUuid('user_bank_account_id')->nullable()->constrained();
            $table->string('reference')->unique();
            $table->string('wallet_address')->nullable();
            $table->unsignedDecimal('asset_amount', 36, 18);
            $table->unsignedDecimal('rate', 15, 2);
            $table->unsignedDecimal('service_charge', 8, 2)->default(0);
            $table->string('status')->default(AssetTransactionStatus::PENDING->value)
                ->comment('Enum:AssetTransactionStatus');
            $table->string('trade_type')->comment('Enum:AssetTransactionTradeType');
            $table->text('comment')->nullable();
            $table->string('proof')->nullable();
            $table->unsignedDecimal('payable_amount', 20, 2);
            $table->text('review_note')->nullable();
            $table->string('review_proof')->nullable();
            $table->unsignedDecimal('review_rate', 15, 2)->nullable();
            $table->foreignUuid('reviewed_by')->nullable()->constrained('admins', 'id');
            $table->timestamp('reviewed_at')->nullable();
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
        Schema::dropIfExists('asset_transactions');
    }
};
