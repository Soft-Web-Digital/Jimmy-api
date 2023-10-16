<?php

use App\Enums\GiftcardStatus;
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
        Schema::create('giftcards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('giftcard_product_id')->constrained();
            $table->foreignUuid('user_bank_account_id')->constrained();
            $table->string('reference')->unique();
            $table->string('status')->default(GiftcardStatus::PENDING->value)->comment('Enum:GiftcardStatus');
            $table->string('trade_type')->comment('Enum:GiftcardTradeType');
            $table->string('card_type')->comment('Enum:GiftcardCardType');
            $table->text('code')->nullable()->comment('encrypted');
            $table->text('pin')->nullable()->comment('encrypted');
            $table->text('card')->nullable()->comment('encrypted URL');
            $table->unsignedDecimal('amount', 15, 2);
            $table->unsignedDecimal('service_charge', 8, 2)->default(0);
            $table->unsignedDecimal('rate', 15, 2);
            $table->unsignedDecimal('payable_amount', 15, 2);
            $table->string('comment')->nullable();
            $table->text('review_note')->nullable();
            $table->string('review_proof')->nullable();
            $table->unsignedDecimal('review_rate', 15, 2)->nullable();
            $table->foreignUuid('reviewed_by')->nullable()->constrained('admins');
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
        Schema::dropIfExists('giftcards');
    }
};
