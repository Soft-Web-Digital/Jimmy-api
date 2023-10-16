<?php

declare(strict_types=1);

namespace App\Services\Transaction\Admin;

use App\DataTransferObjects\TransactionFilterData;
use App\Enums\AssetTransactionStatus;
use App\Enums\GiftcardStatus;
use App\Enums\WalletServiceType;
use App\Enums\WalletTransactionStatus;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    /**
     * Build a query to get giftcard stats.
     *
     * @param \App\DataTransferObjects\TransactionFilterData $transactionFilterData
     * @return \Illuminate\Database\Query\Builder
     */
    public function giftcardStats(TransactionFilterData $transactionFilterData): Builder
    {
        $result = DB::table('giftcards')
            ->select([
                DB::raw('COUNT(*) as total_transactions_count'),
            ])
            ->when(
                $transactionFilterData->getUserId(),
                fn ($query) => $query->where('user_id', $transactionFilterData->getUserId())
            )
            ->when(
                $creationDate = $transactionFilterData->getCreationDate(),
                function ($query) use ($creationDate) {
                    $dates = explode(',', $creationDate);

                    $from = head($dates);
                    $to = count($dates) > 1 ? $dates[1] : null;

                    return $query->where(
                        fn ($query) => $query->whereDate('created_at', '>=', $from)
                            ->when($to, fn ($query) => $query->whereDate('created_at', '<=', $to))
                    );
                }
            );

        foreach (GiftcardStatus::values() as $status) {
            $result
                ->addSelect(
                    DB::raw(
                        "COUNT(CASE WHEN giftcards.status = '{$status}'"
                        . " THEN 1 ELSE NULL END) as total_{$status}_transactions_count"
                    )
                )
                ->addSelect(
                    DB::raw(
                        'CAST(SUM('
                        . "CASE WHEN giftcards.status = '{$status}'"
                        . ' THEN giftcards.payable_amount ELSE 0 END'
                        . ") AS FLOAT) as total_{$status}_transactions_amount"
                    )
                );
        }

        return $result;
    }

    /**
     * Build a query to get asset transaction stats.
     *
     * @param \App\DataTransferObjects\TransactionFilterData $transactionFilterData
     * @return \Illuminate\Database\Query\Builder
     */
    public function assetTransactionStats(TransactionFilterData $transactionFilterData): Builder
    {
        $result = DB::table('asset_transactions')
            ->select([
                DB::raw('COUNT(*) as total_transactions_count'),
            ])
            ->when(
                $transactionFilterData->getUserId(),
                fn ($query) => $query->where('user_id', $transactionFilterData->getUserId())
            )
            ->when(
                $creationDate = $transactionFilterData->getCreationDate(),
                function ($query) use ($creationDate) {
                    $dates = explode(',', $creationDate);

                    $from = head($dates);
                    $to = count($dates) > 1 ? $dates[1] : null;

                    return $query->where(
                        fn ($query) => $query->whereDate('created_at', '>=', $from)
                            ->when($to, fn ($query) => $query->whereDate('created_at', '<=', $to))
                    );
                }
            );

        foreach (AssetTransactionStatus::values() as $status) {
            $result
                ->addSelect(
                    DB::raw(
                        "COUNT(CASE WHEN status = '{$status}'"
                        . " THEN 1 ELSE NULL END) as total_{$status}_transactions_count"
                    )
                )
                ->addSelect(
                    DB::raw(
                        'CAST(SUM('
                        . "CASE WHEN status = '{$status}'"
                        . ' THEN payable_amount ELSE 0 END'
                        . ") AS FLOAT) as total_{$status}_transactions_amount"
                    )
                );
        }

        return $result;
    }

    /**
     * Build a query to get wallet transaction stats.
     *
     * @param \App\DataTransferObjects\TransactionFilterData $transactionFilterData
     * @return \Illuminate\Database\Query\Builder
     */
    public function walletTransactionStats(TransactionFilterData $transactionFilterData): Builder
    {
        $result = DB::table('wallet_transactions')
            ->select([
                DB::raw('COUNT(*) as total_transactions_count'),
            ])
            ->when(
                $transactionFilterData->getUserId(),
                fn ($query) => $query
                    ->where('user_type', (new User())->getMorphClass())
                    ->where('user_id', $transactionFilterData->getUserId())
            )
            ->when(
                $creationDate = $transactionFilterData->getCreationDate(),
                function ($query) use ($creationDate) {
                    $dates = explode(',', $creationDate);

                    $from = head($dates);
                    $to = count($dates) > 1 ? $dates[1] : null;

                    return $query->where(
                        fn ($query) => $query->whereDate('created_at', '>=', $from)
                            ->when($to, fn ($query) => $query->whereDate('created_at', '<=', $to))
                    );
                }
            );

        foreach (WalletServiceType::values() as $service) {
            $result
                ->addSelect(
                    DB::raw(
                        "COUNT(CASE WHEN service = '{$service}'"
                        . " THEN 1 ELSE NULL END) as total_{$service}_transactions_count"
                    )
                )
                ->addSelect(
                    DB::raw(
                        'CAST(SUM('
                        . "CASE WHEN service = '{$service}'"
                        . ' THEN amount ELSE 0 END'
                        . ") AS FLOAT) as total_{$service}_transactions_amount"
                    )
                );
        }

        foreach (WalletTransactionStatus::values() as $status) {
            $result
                ->addSelect(
                    DB::raw(
                        "COUNT(CASE WHEN status = '{$status}'"
                        . " THEN 1 ELSE NULL END) as total_{$status}_transactions_count"
                    )
                )
                ->addSelect(
                    DB::raw(
                        'CAST(SUM('
                        . "CASE WHEN status = '{$status}'"
                        . ' THEN amount ELSE 0 END'
                        . ") AS FLOAT) as total_{$status}_transactions_amount"
                    )
                );

            foreach (WalletServiceType::values() as $service) {
                $result
                    ->addSelect(
                        DB::raw(
                            "COUNT(CASE WHEN status = '{$status}' AND service = '{$service}'"
                            . " THEN 1 ELSE NULL END) as total_{$status}_{$service}_transactions_count"
                        )
                    )
                    ->addSelect(
                        DB::raw(
                            'CAST(SUM('
                            . "CASE WHEN status = '{$status}' AND service = '{$service}'"
                            . ' THEN amount ELSE 0 END'
                            . ") AS FLOAT) as total_{$status}_{$service}_transactions_amount"
                        )
                    );
            }
        }

        return $result;
    }
}
