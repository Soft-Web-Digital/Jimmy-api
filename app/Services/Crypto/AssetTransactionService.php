<?php

declare(strict_types=1);

namespace App\Services\Crypto;

use App\DataTransferObjects\AssetTransactionBreakdownData;
use App\DataTransferObjects\Models\AssetTransactionModelData;
use App\Enums\AssetTransactionStatus;
use App\Enums\AssetTransactionTradeType;
use App\Enums\SystemDataCode;
use App\Events\Admin\AdminNotified;
use App\Exceptions\ExpectationFailedException;
use App\Exceptions\NotAllowedException;
use App\Models\Admin;
use App\Models\Asset;
use App\Models\AssetTransaction;
use App\Models\SystemData;
use App\Models\User;
use App\Models\UserBankAccount;
use App\Notifications\Admin\AssetTransactionUpdateNotification;
use App\Notifications\User\AssetTransactionApprovedNotification;
use App\Notifications\User\AssetTransactionDeclinedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class AssetTransactionService
{
    /**
     * Get the breakdown for the asset transaction.
     *
     * @param \App\DataTransferObjects\Models\AssetTransactionModelData $assetTransactionModelData
     * @param bool $completeApproval
     * @return \App\DataTransferObjects\AssetTransactionBreakdownData
     */
    public function breakdown(AssetTransactionModelData $assetTransactionModelData, bool $completeApproval = true): AssetTransactionBreakdownData
    {
        $serviceCharge = (float) SystemData::query()
            ->where(
                'code',
                $assetTransactionModelData->getTradeType() == AssetTransactionTradeType::BUY
                    ? SystemDataCode::CRYPTO_BUY_SERVICE_CHARGE
                    : SystemDataCode::CRYPTO_SELL_SERVICE_CHARGE
            )
            ->value('content');

        /** @var \App\Models\Asset $asset */
        $asset = Asset::query()->findOrFail($assetTransactionModelData->getAssetId());

        switch ($assetTransactionModelData->getTradeType()) {
            case AssetTransactionTradeType::BUY:
                $rate = $assetTransactionModelData->getRate() ?? $asset->buy_rate;

                $payableAmount = $assetTransactionModelData->getAssetAmount() * $rate;

                if ($completeApproval) {
                    $payableAmount = round($payableAmount + ($payableAmount * ($serviceCharge / 100)), 2);
                }

                throw_if(
                    $payableAmount < 10,
                    NotAllowedException::class,
                    "Asset amount is too small, as it is unrealistic to transfer NGN {$payableAmount}."
                );

                break;

            case AssetTransactionTradeType::SELL:
                $rate = $assetTransactionModelData->getRate() ?? $asset->sell_rate;

                $payableAmount = $assetTransactionModelData->getReviewAmount() ??
                    ($assetTransactionModelData->getAssetAmount() * $rate);

                if ($completeApproval) {
                    $payableAmount = round($payableAmount + ($payableAmount * ($serviceCharge / 100)), 2);
                }

                throw_if(
                    $payableAmount < 10,
                    NotAllowedException::class,
                    "Asset amount is too small, as it is unrealistic to transfer NGN {$payableAmount}."
                );

                break;

            default:
                throw new ExpectationFailedException('Asset trade type is unavailable at the moment');
        }

        return (new AssetTransactionBreakdownData())
            ->setRate($rate)
            ->setServiceCharge($serviceCharge)
            ->setPayableAmount($payableAmount);
    }

    /**
     * Create an asset transaction.
     *
     * @param \App\DataTransferObjects\Models\AssetTransactionModelData $assetTransactionModelData
     * @param \App\Models\User $user
     * @return \App\Models\AssetTransaction
     */
    public function create(AssetTransactionModelData $assetTransactionModelData, User $user): AssetTransaction
    {
        $breakdown = $this->breakdown($assetTransactionModelData);

        $assetTransaction = new AssetTransaction();
        $assetTransaction->reference = strtoupper('AT' . bin2hex(random_bytes(5)) . time());
        $assetTransaction->user_id = $user->id;
        $assetTransaction->network_id = $assetTransactionModelData->getNetworkId();
        $assetTransaction->asset_id = $assetTransactionModelData->getAssetId();
        $assetTransaction->asset_amount = $assetTransactionModelData->getAssetAmount();
        $assetTransaction->rate = $breakdown->getRate();
        $assetTransaction->service_charge = $breakdown->getServiceCharge();
        $assetTransaction->trade_type = $assetTransactionModelData->getTradeType();
        $assetTransaction->comment = $assetTransactionModelData->getComment();
        $assetTransaction->payable_amount = $breakdown->getPayableAmount();

        switch ($assetTransactionModelData->getTradeType()) {
            case AssetTransactionTradeType::BUY:
                $assetTransaction->wallet_address = $assetTransactionModelData->getWalletAddress();
                break;

            case AssetTransactionTradeType::SELL:
                /** @var \App\Models\UserBankAccount $userBankAccount */
                $userBankAccount = UserBankAccount::query()
                    ->findOrFail($assetTransactionModelData->getUserBankAccountId());

                $assetTransaction->bank_id = $userBankAccount->bank_id;
                $assetTransaction->account_name = $userBankAccount->account_name;
                $assetTransaction->account_number = $userBankAccount->account_number;
                break;

            default:
                throw new ExpectationFailedException('Asset trade type is unavailable at the moment');
        }

        $assetTransaction->saveOrFail();

        return $assetTransaction->refresh();
    }

    /**
     * Mark the asset transaction as transferred.
     *
     * @param \App\Models\AssetTransaction $assetTransaction
     * @param string $proof
     * @return \App\Models\AssetTransaction
     */
    public function transfer(AssetTransaction $assetTransaction, string $proof): AssetTransaction
    {
        throw_if(
            $assetTransaction->status !== AssetTransactionStatus::PENDING,
            NotAllowedException::class,
            'Asset transaction current status: ' . $assetTransaction->status->value
        );

        DB::beginTransaction();

        try {
            $assetTransaction->updateOrFail([
                'status' => AssetTransactionStatus::TRANSFERRED,
                'proof' => $proof,
            ]);

            event(new AdminNotified(new AssetTransactionUpdateNotification(
                'Transfer for Asset Transaction: ' . $assetTransaction->trade_type->name,
                "{$assetTransaction->user->full_name} has marked asset {$assetTransaction->trade_type->value} "
                    . "transaction (ref: {$assetTransaction->reference}) as 'transferred'. "
                    . 'Kindly review it as soon as possible.'
            )));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $assetTransaction->withoutRelations()->refresh();
    }

    /**
     * Decline the asset transaction.
     *
     * @param \App\Models\AssetTransaction $assetTransaction
     * @param \App\Models\Admin $admin
     * @param string|null $reviewNote
     * @param array<int, string>|null $reviewProof
     * @return \App\Models\AssetTransaction
     */
    public function decline(
        AssetTransaction $assetTransaction,
        Admin $admin,
        string $reviewNote = null,
        ?array $reviewProof = null,
    ): AssetTransaction {
        throw_if(
            !in_array(
                $assetTransaction->status,
                [AssetTransactionStatus::TRANSFERRED, AssetTransactionStatus::PENDING]
            ),
            NotAllowedException::class,
            "Asset transaction status is currently: {$assetTransaction->status->value}."
        );

        DB::beginTransaction();

        try {
            $assetTransaction->updateOrFail([
                'status' => AssetTransactionStatus::DECLINED,
                'review_note' => $reviewNote,
                'review_proof' => $reviewProof,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            $assetTransaction->notifyUser(
                new AssetTransactionDeclinedNotification(
                    $assetTransaction->trade_type,
                    $assetTransaction->reference,
                    $assetTransaction->review_note,
                    $assetTransaction->review_proof
                )
            );

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $assetTransaction->withoutRelations()->refresh();
    }

    /**
     * Approve the asset transaction.
     *
     * @param \App\Models\AssetTransaction $assetTransaction
     * @param \App\Models\Admin $admin
     * @param bool $completeApproval
     * @param float|null $reviewAmount
     * @param string|null $reviewNote
     * @param array<int, string>|null $reviewProof
     * @return \App\Models\AssetTransaction
     */
    public function approve(
        AssetTransaction $assetTransaction,
        Admin $admin,
        bool $completeApproval,
        float|null $reviewAmount = null,
        string $reviewNote = null,
        ?array $reviewProof = null,
    ): AssetTransaction {
        throw_if(
            !in_array(
                $assetTransaction->status,
                [AssetTransactionStatus::TRANSFERRED, AssetTransactionStatus::PENDING]
            ),
            NotAllowedException::class,
            "Asset transaction status is currently: {$assetTransaction->status->value}."
        );

        DB::beginTransaction();

        try {
            $newBreakdown = null;
            $status = AssetTransactionStatus::APPROVED;

            if (!$completeApproval) {
//                $newBreakdown = $this->breakdown(
//                    (new AssetTransactionModelData())
//                        ->setTradeType($assetTransaction->trade_type)
//                        ->setAssetId($assetTransaction->asset_id)
//                        ->setAssetAmount($assetTransaction->asset_amount)
//                        ->setReviewAmount($reviewAmount),
//                    false
//                );
                $status = AssetTransactionStatus::PARTIALLYAPPROVED;
            }

            // Update the asset transaction
            $assetTransaction->updateOrFail([
                'status' => $status,
                'review_note' => $reviewNote,
                'review_proof' => $reviewProof,
//                'review_rate' => $newBreakdown?->getRate() ?? $assetTransaction->rate,
                'review_amount' => $reviewAmount,
//                'payable_amount' => $newBreakdown?->getPayableAmount() ?? $assetTransaction->payable_amount,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            if ($completeApproval) {
                $assetTransaction->creditReferee();
            }

            $assetTransaction->notifyUser(
                new AssetTransactionApprovedNotification(
                    $assetTransaction->trade_type,
                    $assetTransaction->reference,
                    $assetTransaction->review_note,
                    $assetTransaction->review_proof
                )
            );

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $assetTransaction->withoutRelations()->refresh();
    }

    /**
     * Get the statistics for the user.
     *
     * @param \App\Models\User $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getStats(User $user)
    {
        $stats = AssetTransaction::query()
            ->select([
                DB::raw('COUNT(*) as total_transactions_count'),
                DB::raw(
                    'CAST(SUM('
                    . 'CASE WHEN asset_transactions.status = \'' . AssetTransactionStatus::APPROVED->value . '\''
                    . ' OR asset_transactions.status = \'' . AssetTransactionStatus::PARTIALLYAPPROVED->value . '\''
                    . ' THEN asset_transactions.payable_amount ELSE 0 END'
                    . ') AS FLOAT) as total_transactions_amount'
                ),
            ]);

        foreach (AssetTransactionTradeType::values() as $tradeType) {
            $stats->addSelect(
                DB::raw(
                    "COUNT(CASE WHEN `trade_type` = '{$tradeType}' THEN 1 ELSE NULL END) as total_{$tradeType}_count"
                ),
                DB::raw(
                    "CAST(SUM(CASE WHEN `trade_type` = '{$tradeType}' THEN payable_amount ELSE 0 END) AS FLOAT)"
                    . " as total_{$tradeType}_amount"
                ),
                DB::raw(
                    "COUNT(CASE WHEN `trade_type` = '{$tradeType}' THEN 1 ELSE NULL END) as total_{$tradeType}_count"
                ),
                DB::raw(
                    "CAST(SUM(CASE WHEN `trade_type` = '{$tradeType}' THEN payable_amount ELSE 0 END) AS FLOAT)"
                    . " as total_{$tradeType}_amount"
                ),
            );
        }

        foreach (AssetTransactionStatus::values() as $status) {
            $stats->addSelect(
                DB::raw(
                    "COUNT(CASE WHEN `status` = '{$status}' THEN 1 ELSE NULL END) as total_{$status}_count"
                ),
                DB::raw(
                    "CAST(SUM(CASE WHEN `status` = '{$status}' THEN payable_amount ELSE 0 END)"
                    . " AS FLOAT) as total_{$status}_amount"
                ),
            );

            foreach (AssetTransactionTradeType::values() as $tradeType) {
                $stats
                    ->addSelect(
                        DB::raw(
                            "COUNT(CASE WHEN `trade_type` = '{$tradeType}' AND `status` = '{$status}' THEN 1"
                            . " ELSE NULL END) as total_{$status}_{$tradeType}_count"
                        ),
                        DB::raw(
                            "CAST(SUM(CASE WHEN `trade_type` = '{$tradeType}' AND `status` = '{$status}' THEN"
                            . " payable_amount ELSE 0 END) AS FLOAT) as total_{$status}_{$tradeType}_amount"
                        ),
                    )
                ;
            }
        }

        return $stats
            ->where('user_id', $user->id)
            ->get();
    }

    public function createInvoice(AssetTransaction $assetTransaction, User $user)
    {
        $data = [
            'price' => $assetTransaction->amount,
            'currency' => $assetTransaction->amount,
            'token' => '',
            'buyer' => [
                'name' => $user->fullName,
                'email' => $user->email,
                'phone' => $user->phone_number,
                'country' => $user->country->name
            ]
        ];

        Http::withHeaders([])->post('bitpay.com/invoices', $data);
    }
}
