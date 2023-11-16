<?php

namespace App\Console\Commands;

use App\DataTransferObjects\WalletData;
use App\Enums\SystemDataCode;
use App\Enums\WalletServiceType;
use App\Enums\WalletTransactionStatus;
use App\Models\Referral;
use App\Models\SystemData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreditRefereeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ref:credit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Credit referees that have accumulated the required amount.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $systemData = SystemData::query()->where('code', 'REFMA')->first();
        /** @var Referral $referral */
        foreach (Referral::query()->where('cumulative_amount', '>=', ($systemData?->content ?? SystemDataCode::REFERRAL_MINIMUM_AMOUNT->defaultContent()))->where('paid', false)->get() as $referral) {
            $referee = $referral->referee;
            DB::beginTransaction();
            $referee->deposit(
                (new WalletData())
                    ->setCauser($referral->referred)
                    ->setComment('Referral bonus')
                    ->setAmount($referral->amount)
                    ->setWalletTransactionStatus(WalletTransactionStatus::COMPLETED)
                    ->setWalletServiceType(WalletServiceType::OTHER)
            );
            $referral->update(['paid' => true]);
            DB::commit();
        }
        $this->info('Referees credited!');

        return self::SUCCESS;
    }
}
