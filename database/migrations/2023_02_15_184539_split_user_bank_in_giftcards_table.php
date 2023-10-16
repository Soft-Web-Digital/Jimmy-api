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
        Schema::table('giftcards', function (Blueprint $table) {
            $table->after('giftcard_product_id', function (Blueprint $table) {
                $table->foreignUuid('bank_id')->nullable()->constrained();
                $table->foreignUuid('user_id')->nullable()->constrained();
                $table->string('account_number')->nullable();
                $table->string('account_name')->nullable();
            });
        });

        DB::table('giftcards')
            ->select([
                'giftcards.id',
                'user_bank_accounts.bank_id',
                'user_bank_accounts.user_id',
                'user_bank_accounts.account_name',
                'user_bank_accounts.account_number',
            ])
            ->join('user_bank_accounts', 'user_bank_accounts.id', '=', 'giftcards.user_bank_account_id')
            ->orderBy('giftcards.id')
            ->chunk(100, function ($giftcards) {
                foreach ($giftcards as $giftcard) {
                    DB::table('giftcards')
                        ->where('id', $giftcard->id)
                        ->update([
                            'bank_id' => $giftcard->bank_id,
                            'user_id' => $giftcard->user_id,
                            'account_name' => $giftcard->account_name,
                            'account_number' => $giftcard->account_number,
                        ]);
                }
            });

        Schema::table('giftcards', function (Blueprint $table) {
            $table->uuid('bank_id')->nullable(false)->change();
            $table->uuid('user_id')->nullable(false)->change();
            $table->string('account_number')->nullable(false)->change();
            $table->string('account_name')->nullable(false)->change();

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
        Schema::table('giftcards', function (Blueprint $table) {
            //
        });
    }
};
