<?php

use App\Enums\WalletTransactionStatus;
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
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->after('causer_type', function (Blueprint $table) {
                $table->foreignUuid('bank_id')->nullable()->constrained();
                $table->string('account_number')->nullable();
                $table->string('account_name')->nullable();
            });

            $table->string('status')
                ->after('type')
                ->default(WalletTransactionStatus::COMPLETED->value)
                ->comment('Enum:WalletTransactionStatus');

            $table->text('comment')->nullable()->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'account_number',
                'account_name',
                'comment',
            ]);
            $table->dropConstrainedForeignId('bank_id');
        });
    }
};
