<?php

declare(strict_types=1);

namespace App\Services\Transaction\User;

use App\DataTransferObjects\TransactionFilterData;
use App\Enums\AssetTransactionStatus;
use App\Enums\GiftcardStatus;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    /**
     * Build a query to get giftcard records.
     *
     * @param \App\DataTransferObjects\TransactionFilterData $transactionFilterData
     * @return \Illuminate\Database\Query\Builder
     */
    public function giftcardRecords(TransactionFilterData $transactionFilterData): Builder
    {
        return DB::table('giftcards')
            ->select([
                'giftcards.id as id',
                DB::raw('\'giftcard\' as \'type\''),
                'giftcards.reference as reference',
                'giftcards.status as status',
                'giftcards.trade_type as trade_type',
                'currencies.code as currency',
                DB::raw('CAST(giftcards.amount AS FLOAT) as amount'),
                DB::raw('CAST(giftcards.payable_amount AS FLOAT) as payable_amount'),
                DB::raw('CAST(giftcards.rate AS FLOAT) as rate'),
                DB::raw('CAST(giftcards.review_rate AS FLOAT) as review_rate'),
                DB::raw('CAST(giftcards.service_charge AS FLOAT) as service_charge'),
                'giftcard_categories.name as category_name',
                'giftcard_categories.icon as category_icon',
                'giftcards.created_at as created_at',
            ])
            ->join('giftcard_products', 'giftcard_products.id', '=', 'giftcards.giftcard_product_id')
            ->join('giftcard_categories', 'giftcard_categories.id', '=', 'giftcard_products.giftcard_category_id')
            ->join('currencies', 'currencies.id', '=', 'giftcard_products.currency_id')
            ->when(
                $transactionFilterData->getUserId(),
                fn ($query) => $query->where('user_id', $transactionFilterData->getUserId())
            )
            ->when(
                $transactionFilterData->getStatus(),
                fn ($query) => $query->where('status', $transactionFilterData->getStatus())
            )
            ->when(
                $creationDate = $transactionFilterData->getCreationDate(),
                function ($query) use ($creationDate) {
                    $dates = explode(',', $creationDate);

                    $from = head($dates);
                    $to = count($dates) > 1 ? $dates[1] : null;

                    return $query->where(
                        fn ($query) => $query->whereDate('giftcards.created_at', '>=', $from)
                            ->when($to, fn ($query) => $query->whereDate('giftcards.created_at', '<=', $to))
                    );
                }
            )
            ->when(
                $payableAmount = $transactionFilterData->getPayableAmount(),
                function ($query) use ($payableAmount) {
                    $amounts = explode(',', $payableAmount);

                    $from = head($amounts);
                    $to = count($amounts) > 1 ? $amounts[1] : null;

                    return $query->where(
                        fn ($query) => $query->where('giftcards.payable_amount', '>=', $from)
                            ->when($to, fn ($query) => $query->where('giftcards.payable_amount', '<=', $to))
                    );
                }
            );
    }

    /**
     * Build a query to get giftcard stats.
     *
     * @param \App\DataTransferObjects\TransactionFilterData $transactionFilterData
     * @return \Illuminate\Database\Query\Builder
     */
    public function giftcardStats(TransactionFilterData $transactionFilterData): Builder
    {
        return DB::table('giftcards')
            ->select([
                DB::raw('\'giftcard\' as \'type\''),
                DB::raw(
                    'COUNT(CASE WHEN giftcards.status = \'' . GiftcardStatus::APPROVED->value
                    . '\' OR giftcards.status = \'' . GiftcardStatus::PARTIALLYAPPROVED->value
                    . '\' THEN 1 ELSE NULL END) as total_transactions_count'
                ),
                DB::raw(
                    'CAST(SUM('
                    . 'CASE WHEN giftcards.status = \'' . GiftcardStatus::APPROVED->value . '\''
                    . ' OR giftcards.status = \'' . GiftcardStatus::PARTIALLYAPPROVED->value . '\''
                    . ' THEN giftcards.payable_amount ELSE 0 END'
                    . ') AS FLOAT) as total_transactions_amount'
                ),
            ])
            ->when(
                $transactionFilterData->getUserId(),
                fn ($query) => $query->where('user_id', $transactionFilterData->getUserId())
            );
    }

    /**
     * Build a query to get asset transaction records.
     *
     * @param \App\DataTransferObjects\TransactionFilterData $transactionFilterData
     * @return \Illuminate\Database\Query\Builder
     */
    public function assetTransactionRecords(TransactionFilterData $transactionFilterData): Builder
    {
        return DB::table('asset_transactions')
            ->select([
                'asset_transactions.id as id',
                DB::raw('\'asset_transaction\' as \'type\''),
                'asset_transactions.reference as reference',
                'asset_transactions.status as status',
                'asset_transactions.trade_type as trade_type',
                'assets.code as currency',
                DB::raw('CAST(asset_transactions.asset_amount AS FLOAT) as amount'),
                DB::raw('CAST(asset_transactions.payable_amount AS FLOAT) as payable_amount'),
                DB::raw('CAST(asset_transactions.rate AS FLOAT) as rate'),
                DB::raw('CAST(asset_transactions.review_rate AS FLOAT) as review_rate'),
                DB::raw('CAST(asset_transactions.service_charge AS FLOAT) as service_charge'),
                'assets.name as category_name',
                DB::raw(
                    'CASE WHEN assets.icon LIKE "http%" THEN assets.icon '
                    . 'ELSE CONCAT(\''.config('app.url').'\', \'/\', assets.icon) '
                    . 'END AS category_icon'
                ),
                'asset_transactions.created_at as created_at',
            ])
            ->join('assets', 'assets.id', '=', 'asset_transactions.asset_id')
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
                        fn ($query) => $query->whereDate('asset_transactions.created_at', '>=', $from)
                            ->when($to, fn ($query) => $query->whereDate('asset_transactions.created_at', '<=', $to))
                    );
                }
            )
            ->when(
                $transactionFilterData->getStatus(),
                fn ($query) => $query->where('status', $transactionFilterData->getStatus())
            )
            ->when(
                $payableAmount = $transactionFilterData->getPayableAmount(),
                function ($query) use ($payableAmount) {
                    $amounts = explode(',', $payableAmount);

                    $from = head($amounts);
                    $to = count($amounts) > 1 ? $amounts[1] : null;

                    return $query->where(
                        fn ($query) => $query->where('asset_transactions.payable_amount', '>=', $from)
                            ->when($to, fn ($query) => $query->where('asset_transactions.payable_amount', '<=', $to))
                    );
                }
            );
    }

    /**
     * Build a query to get asset transaction stats.
     *
     * @param \App\DataTransferObjects\TransactionFilterData $transactionFilterData
     * @return \Illuminate\Database\Query\Builder
     */
    public function assetTransactionStats(TransactionFilterData $transactionFilterData): Builder
    {
        return DB::table('asset_transactions')
            ->select([
                DB::raw('\'asset_transaction\' as \'type\''),
                DB::raw(
                    'COUNT(CASE WHEN asset_transactions.status = \'' . AssetTransactionStatus::APPROVED->value
                    . '\' OR asset_transactions.status = \'' . AssetTransactionStatus::PARTIALLYAPPROVED->value
                    . '\' THEN 1 ELSE NULL END) as total_transactions_count'
                ),
                DB::raw(
                    'CAST(SUM('
                    . 'CASE WHEN asset_transactions.status = \'' . AssetTransactionStatus::APPROVED->value . '\''
                    . ' OR asset_transactions.status = \'' . AssetTransactionStatus::PARTIALLYAPPROVED->value . '\''
                    . ' THEN asset_transactions.payable_amount ELSE 0 END'
                    . ') AS FLOAT) as total_transactions_amount'
                ),
            ])
            ->when(
                $transactionFilterData->getUserId(),
                fn ($query) => $query->where('user_id', $transactionFilterData->getUserId())
            );
    }
}
