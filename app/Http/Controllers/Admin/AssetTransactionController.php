<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApiErrorCode;
use App\Enums\AssetTransactionStatus;
use App\Exports\AssetTransactionSheet;
use App\Exports\DataExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApproveAssetTransactionRequest;
use App\Http\Requests\Admin\DeclineAssetTransactionRequest;
use App\Models\Asset;
use App\Models\AssetTransaction;
use App\Models\User;
use App\Services\Crypto\AssetTransactionService;
use App\Spatie\QueryBuilder\IncludeSelectFields;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class AssetTransactionController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param \App\Models\AssetTransaction $assetTransaction
     */
    public function __construct(public AssetTransaction $assetTransaction)
    {
    }

    /**
     * Display a listing of the resource.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index(Request $request): Response
    {
        $AssetTransactions = QueryBuilder::for($this->assetTransaction->query())
            ->allowedFields($this->assetTransaction->getQuerySelectables())
            ->allowedFilters([
                'trade_type',
                'status',
                AllowedFilter::scope('creation_date'),
                AllowedFilter::exact('network_id'),
                AllowedFilter::exact('asset_id'),
                AllowedFilter::exact('user_id'),
                AllowedFilter::exact('reference'),
                AllowedFilter::exact('payable_amount'),
            ])
            ->allowedIncludes([
                AllowedInclude::custom('network', new IncludeSelectFields([
                    'id',
                    'name',
                ])),
                AllowedInclude::custom('asset', new IncludeSelectFields([
                    'id',
                    'code',
                    'buy_rate',
                ])),
                AllowedInclude::custom('user', new IncludeSelectFields([
                    'id',
                    'firstname',
                    'lastname',
                    'email',
                    'phone_number',
                ])),
                AllowedInclude::custom('bank', new IncludeSelectFields([
                    'id',
                    'name',
                ])),
            ])
            ->defaultSort('-updated_at')
            ->allowedSorts([
                'trade_type',
                'status',
                'reviewed_at',
                'created_at',
                'updated_at',
                'deleted_at',
            ])
            ->paginate((int) $request->per_page)
            ->withQueryString();

        return ResponseBuilder::asSuccess()
            ->withMessage('Asset transactions fetched successfully')
            ->withData([
                'asset_transactions' => $AssetTransactions,
            ])
            ->build();
    }

    /**
     * Display the specified resource.
     *
     * @param string $assetTransaction
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function show(string $assetTransaction): Response
    {
        $assetTransaction = QueryBuilder::for(
            $this->assetTransaction->withTrashed()
        )
            ->allowedIncludes([
                AllowedInclude::custom('network', new IncludeSelectFields([
                    'id',
                    'name',
                ])),
                AllowedInclude::custom('asset', new IncludeSelectFields([
                    'id',
                    'code',
                    'buy_rate',
                ])),
                AllowedInclude::custom('user', new IncludeSelectFields([
                    'id',
                    'firstname',
                    'lastname',
                    'email',
                    'phone_number',
                ])),
                AllowedInclude::custom('bank', new IncludeSelectFields([
                    'id',
                    'name',
                ])),
                AllowedInclude::custom('reviewer', new IncludeSelectFields([
                    'id',
                    'firstname',
                    'lastname',
                    'email'
                ])),
            ])
            ->where(
                fn ($query) => $query->where('id', $assetTransaction)->orWhere('reference', $assetTransaction)
            )
            ->firstOrFail();

        return ResponseBuilder::asSuccess()
            ->withMessage('Asset transaction fetched successfully')
            ->withData([
                'asset_transaction' => $assetTransaction,
            ])
            ->build();
    }

    /**
     * Decline the specified resource from storage.
     *
     * @param \App\Http\Requests\Admin\DeclineAssetTransactionRequest $request
     * @param \App\Models\AssetTransaction $assetTransaction
     * @param \App\Services\Crypto\AssetTransactionService $assetTransactionService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function decline(
        DeclineAssetTransactionRequest $request,
        AssetTransaction $assetTransaction,
        AssetTransactionService $assetTransactionService
    ): Response {
        $assetTransaction = $assetTransactionService->decline(
            $assetTransaction,
            $request->user('api_admin'), // @phpstan-ignore-line
            $request->review_note,
            $request->review_proof,
        );

        return ResponseBuilder::asSuccess()
            ->withMessage('Asset transaction declined successfully')
            ->withData([
                'asset_transaction' => $assetTransaction,
            ])
            ->build();
    }

    /**
     * Approve the specified resource from storage.
     *
     * @param \App\Http\Requests\Admin\ApproveAssetTransactionRequest $request
     * @param \App\Models\AssetTransaction $assetTransaction
     * @param \App\Services\Crypto\AssetTransactionService $assetTransactionService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function approve(
        ApproveAssetTransactionRequest $request,
        AssetTransaction $assetTransaction,
        AssetTransactionService $assetTransactionService
    ): Response {
        $assetTransaction = $assetTransactionService->approve(
            $assetTransaction,
            $request->user('api_admin'), // @phpstan-ignore-line
            (bool) $request->complete_approval,
            $request->review_amount,
            $request->review_note,
            $request->review_proof
        );

        return ResponseBuilder::asSuccess()
            ->withMessage('Asset transaction approved successfully')
            ->withData([
                'asset_transaction' => $assetTransaction,
            ])
            ->build();
    }

    /**
     * Export asset transactions to a spreadsheet file.
     *
     * @return Response
     */
    public function export(Request $request): Response
    {
        $total = AssetTransaction::query()->count();
        $limit = $request->query('limit') ?? 5000;
        $offset = $request->query('offset') ?? 0;
        $excel = new DataExport(Asset::class, $total, AssetTransactionSheet::class, $offset, $limit);
        $path = 'exports/assets.xlsx';
        if (Excel::store($excel, $path, 'public')) {
            return ResponseBuilder::asSuccess()
                ->withMessage('Asset transactions exported successfully.')
                ->withData([
                    'path' => asset("storage/{$path}")
                ])
                ->build();
        }

        return ResponseBuilder::asError(ApiErrorCode::GENERAL_ERROR->value)
            ->withMessage('Unable to export asset transactions.')
            ->build();
    }

    public function topTraders(Request $request): Response
    {
        $from = $request->query('from');
        $to = $request->query('to') ?? now();
        $query = User::query()
            ->withCount([
                'assetTransactions' => fn ($query) => $query->when(
                    $from,
                    fn () => $query->whereBetween('created_at', [$from, $to])
                )->whereIn('status', [AssetTransactionStatus::APPROVED, AssetTransactionStatus::PARTIALLYAPPROVED])
            ])
            ->withSum([
                'assetTransactions' => fn ($query) => $query->when(
                    $from,
                    fn () => $query->whereBetween('created_at', [$from, $to])
                )->whereIn('status', [AssetTransactionStatus::APPROVED, AssetTransactionStatus::PARTIALLYAPPROVED])
            ], 'payable_amount')
            ->having('asset_transactions_count', '>', 0)
            ->orderByDesc('asset_transactions_sum_payable_amount');

        return ResponseBuilder::asSuccess()
            ->withMessage('Top traders fetched successfully.')
            ->withData([
                'users' => $query->limit($request->query('limit') ?? 10)
                    ->get($request->query('select') ? explode(',', $request->query('select')) : ['*']),
                'total_traders' => $query->count(),
                'total_trades' => (int) $query->sum('asset_transactions_count'),
                'total_traded' => (int) $query->sum('asset_transactions_sum_payable_amount'),
            ])
            ->build();
    }
}
