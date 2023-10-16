<?php

namespace App\Http\Controllers\User;

use App\DataTransferObjects\Models\AssetTransactionModelData;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\BreakdownAssetTransactionRequest;
use App\Http\Requests\User\StoreAssetTransactionRequest;
use App\Http\Requests\User\UploadAssetTransactionProofRequest;
use App\Models\AssetTransaction;
use App\Services\Crypto\AssetTransactionService;
use App\Spatie\QueryBuilder\IncludeSelectFields;
use Illuminate\Http\Request;
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
        $assetTransactions = QueryBuilder::for(
            $this->assetTransaction->query()->where('user_id', $request->user()->id)
        )
            ->allowedFields($this->assetTransaction->getQuerySelectables())
            ->allowedFilters([
                'status',
                'trade_type',
                AllowedFilter::scope('creation_date'),
                AllowedFilter::exact('network_id'),
                AllowedFilter::exact('asset_id'),
                AllowedFilter::exact('reference'),
                AllowedFilter::scope('payable_amount'),
                AllowedFilter::scope('asset_amount'),
            ])
            ->allowedIncludes([
                AllowedInclude::custom('network', new IncludeSelectFields([
                    'id',
                    'name',
                ])),
                AllowedInclude::custom('asset', new IncludeSelectFields([
                    'id',
                    'name',
                    'code',
                    'icon',
                ])),
                AllowedInclude::custom('bank', new IncludeSelectFields([
                    'id',
                    'name',
                ])),
            ])
            ->defaultSort('-updated_at')
            ->allowedSorts([
                'status',
                'trade_type',
                'created_at',
                'updated_at',
            ])
            ->paginate((int) $request->per_page)
            ->withQueryString();

        return ResponseBuilder::asSuccess()
            ->withMessage('Asset transactions fetched successfully')
            ->withData([
                'asset_transactions' => $assetTransactions,
            ])
            ->build();
    }

    /**
     * Get asset transaction stats.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Services\Crypto\AssetTransactionService $assetTransactionService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getStats(Request $request, AssetTransactionService $assetTransactionService): Response
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $stats = $assetTransactionService->getStats($user);

        return ResponseBuilder::asSuccess()
            ->withMessage('Asset transaction stats fetched successfully.')
            ->withData([
                'stats' => $stats,
            ])
            ->build();
    }

    /**
     * Get the breakdown for the giftcard transaction.
     *
     * @param \App\Http\Requests\User\BreakdownAssetTransactionRequest $request
     * @param \App\Services\Crypto\AssetTransactionService $assetTransactionService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function breakdown(
        BreakdownAssetTransactionRequest $request,
        AssetTransactionService $assetTransactionService
    ): Response {
        $breakdown = $assetTransactionService->breakdown(
            (new AssetTransactionModelData())
                ->setTradeType($request->trade_type)
                ->setAssetId($request->asset_id)
                ->setAssetAmount((float) $request->asset_amount)
        );

        return ResponseBuilder::asSuccess()
            ->withMessage('Asset transaction breakdown fetched successfully')
            ->withData([
                'breakdown' => [
                    'rate' => $breakdown->getRate(),
                    'service_charge' => $breakdown->getServiceCharge(),
                    'payable_amount' => $breakdown->getPayableAmount(),
                ],
            ])
            ->build();
    }

    /**
     * Create an asset transaction.
     *
     * @param \App\Http\Requests\User\StoreAssetTransactionRequest $request
     * @param \App\Services\Crypto\AssetTransactionService $assetTransactionService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function store(
        StoreAssetTransactionRequest $request,
        AssetTransactionService $assetTransactionService
    ): Response {
        $assetTransaction = $assetTransactionService->create(
            (new AssetTransactionModelData())
                ->setTradeType($request->trade_type)
                ->setNetworkId($request->network_id)
                ->setAssetId($request->asset_id)
                ->setAssetAmount((float) $request->asset_amount)
                ->setComment($request->comment)
                ->setWalletAddress($request->wallet_address)
                ->setUserBankAccountId($request->user_bank_account_id),
            $request->user()
        );

        return ResponseBuilder::asSuccess()
            ->withHttpCode(Response::HTTP_CREATED)
            ->withMessage('Asset transaction created successfully')
            ->withData([
                'asset_transaction' => $assetTransaction,
            ])
            ->build();
    }

    /**
     * Display the specified resource.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $assetTransaction
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function show(Request $request, string $assetTransaction): Response
    {
        $assetTransaction = QueryBuilder::for($this->assetTransaction->query())
            ->allowedIncludes([
                AllowedInclude::custom('network', new IncludeSelectFields([
                    'id',
                    'name',
                    'wallet_address',
                ])),
                AllowedInclude::custom('asset', new IncludeSelectFields([
                    'id',
                    'name',
                    'code',
                    'icon',
                ])),
                AllowedInclude::custom('reviewer', new IncludeSelectFields([
                    'id',
                    'firstname',
                    'lastname',
                ])),
                AllowedInclude::custom('bank', new IncludeSelectFields([
                    'id',
                    'name',
                ])),
            ])
            ->where('user_id', $request->user()->id)
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
     * Mark asset transaction as transferred.
     *
     * @param \App\Http\Requests\User\UploadAssetTransactionProofRequest $request
     * @param \App\Models\AssetTransaction $assetTransaction
     * @param \App\Services\Crypto\AssetTransactionService $assetTransactionService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function transfer(
        UploadAssetTransactionProofRequest $request,
        AssetTransaction $assetTransaction,
        AssetTransactionService $assetTransactionService
    ): Response {
        $assetTransactionService->transfer($assetTransaction, $request->proof);

        return ResponseBuilder::asSuccess()
            ->withMessage('Asset transaction marked as transferred successfully. Admins have been notified.')
            ->withData([
                'asset_transaction' => $assetTransaction,
            ])
            ->build();
    }
}
