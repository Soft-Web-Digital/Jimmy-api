<?php

namespace App\Http\Controllers\User;

use App\DataTransferObjects\WalletData;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreWalletWithdrawalRequest;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use App\Spatie\QueryBuilder\IncludeSelectFields;
use Illuminate\Http\Request;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class WalletTransactionController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param \App\Models\WalletTransaction $walletTransaction
     */
    public function __construct(public WalletTransaction $walletTransaction)
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
        /** @var \App\Models\User $user */
        $user = $request->user();

        $walletTransactions = QueryBuilder::for(
            $this->walletTransaction->query()->whereMorphedTo('user', $user)->select([
                'id',
                'bank_id',
                'account_name',
                'account_number',
                'amount',
                'service',
                'type',
                'status',
                'summary',
                'admin_note',
                'receipt',
                'created_at',
            ])
        )
            ->allowedFilters(
                'service',
                'type',
                'status',
                AllowedFilter::scope('creation_date'),
                AllowedFilter::scope('amount'),
            )
            ->allowedIncludes([
                AllowedInclude::custom('bank', new IncludeSelectFields([
                    'id',
                    'name',
                ])),
            ])
            ->defaultSort('-created_at')
            ->allowedSorts([
                'amount',
                'created_at',
            ])
            ->paginate((int) $request->per_page)
            ->withQueryString();

        return ResponseBuilder::asSuccess()
            ->withMessage('Wallet transactions fetched successfully.')
            ->withData([
                'wallet_transactions' => $walletTransactions,
            ])
            ->build();
    }

    /**
     * Display a specified resource.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $walletTransaction
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function show(Request $request, string $walletTransaction): Response
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $walletTransaction = QueryBuilder::for(
            $this->walletTransaction->query()->whereMorphedTo('user', $user)->select([
                'id',
                'bank_id',
                'account_name',
                'account_number',
                'amount',
                'service',
                'type',
                'status',
                'summary',
                'admin_note',
                'receipt',
                'created_at',
            ])
        )
            ->allowedIncludes([
                AllowedInclude::custom('bank', new IncludeSelectFields([
                    'id',
                    'name',
                ])),
            ])
            ->findOrFail($walletTransaction);

        return ResponseBuilder::asSuccess()
            ->withMessage('Wallet transaction fetched successfully.')
            ->withData([
                'wallet_transaction' => $walletTransaction,
            ])
            ->build();
    }

    /**
     * Request for a withdrawal.
     *
     * @param \App\Http\Requests\User\StoreWalletWithdrawalRequest $request
     * @param \App\Services\WalletService $walletService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function withdraw(StoreWalletWithdrawalRequest $request, WalletService $walletService): Response
    {
        $walletTransaction = $walletService->requestWithdrawal(
            $request->user(),
            (new WalletData())
                ->setCauser($request->user())
                ->setUserBankAccountId($request->user_bank_account_id)
                ->setAmount($request->amount)
                ->setComment($request->comment)
        );

        return ResponseBuilder::asSuccess()
            ->withHttpCode(Response::HTTP_CREATED)
            ->withMessage('Wallet withdrawal request submitted successfully.')
            ->withData([
                'wallet_transaction' => $walletTransaction,
            ])
            ->build();
    }

    /**
     * Close the specified resource from storage.
     *
     * @param \App\Models\WalletTransaction $walletTransaction
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function close(WalletTransaction $walletTransaction): Response
    {
        $this->authorize('update', $walletTransaction);

        $walletTransaction->close();

        return ResponseBuilder::asSuccess()
            ->withMessage('Wallet transaction closed successfully')
            ->withData([
                'wallet_transaction' => $walletTransaction,
            ])
            ->build();
    }
}
