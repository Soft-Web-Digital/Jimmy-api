<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        Schema::table('asset_transactions', function (Blueprint $table) {
            $table->after('user_id', function (Blueprint $table) {
                $table->foreignUuid('bank_id')->nullable()->constrained();
                $table->string('account_number')->nullable();
                $table->string('account_name')->nullable();
            });
        });

        DB::table('asset_transactions')
            ->select([
                'asset_transactions.id',
                'user_bank_accounts.bank_id',
                'user_bank_accounts.account_name',
                'user_bank_accounts.account_number',
            ])
            ->join('user_bank_accounts', 'user_bank_accounts.id', '=', 'asset_transactions.user_bank_account_id')
            ->orderBy('asset_transactions.id')
            ->chunk(100, function ($assetTransactions) {
                foreach ($assetTransactions as $assetTransaction) {
                    DB::table('asset_transactions')
                        ->where('id', $assetTransaction->id)
                        ->update([
                            'bank_id' => $assetTransaction->bank_id,
                            'account_name' => $assetTransaction->account_name,
                            'account_number' => $assetTransaction->account_number,
                        ]);
                }
            });

        Schema::table('asset_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_bank_account_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('asset_transactions', function (Blueprint $table) {
            //
        });
    }
};
