<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApproveWalletTransactionRequest;
use App\Http\Requests\Admin\DeclineWalletTransactionRequest;
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
        $walletTransactions = QueryBuilder::for(
            $this->walletTransaction->query()
        )
            ->allowedFields($this->walletTransaction->getQuerySelectables())
            ->allowedFilters([
                'status',
                'service',
                'type',
                AllowedFilter::exact('user_id'),
            ])
            ->allowedIncludes([
                AllowedInclude::custom('user', new IncludeSelectFields([
                    'id',
                    'firstname',
                    'lastname',
                    'email',
                    'wallet_balance',
                ])),
            ])
            ->defaultSort('-created_at')
            ->allowedSorts([
                'status',
                'created_at',
                'updated_at',
            ])
            ->paginate((int) $request->per_page)
            ->withQueryString();

        return ResponseBuilder::asSuccess()
            ->withMessage('Wallet transactions fetched successfully')
            ->withData([
                'wallet_transactions' => $walletTransactions,
            ])
            ->build();
    }

    /**
     * Display the specified resource.
     *
     * @param string $walletTransaction
     * @param \App\Services\WalletService $walletService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function show(string $walletTransaction, WalletService $walletService): Response
    {
        $walletTransaction = $walletService->validate($walletTransaction, [
            'bank:id,name',
            'user:id,firstname,lastname,email,wallet_balance',
        ]);

        return ResponseBuilder::asSuccess()
            ->withMessage('Wallet transaction fetched successfully')
            ->withData([
                'wallet_transaction' => $walletTransaction,
            ])
            ->build();
    }

    /**
     * Decline the specified resource from storage.
     *
     * @param \App\Http\Requests\Admin\DeclineWalletTransactionRequest $request
     * @param \App\Models\WalletTransaction $walletTransaction
     * @param \App\Services\WalletService $walletService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function decline(
        DeclineWalletTransactionRequest $request,
        WalletTransaction $walletTransaction,
        WalletService $walletService
    ): Response {
        $walletService->decline($walletTransaction, $request->note);

        return ResponseBuilder::asSuccess()
            ->withMessage('Wallet transaction declined successfully')
            ->withData([
                'wallet_transaction' => $walletTransaction,
            ])
            ->build();
    }

    /**
     * Approve the specified resource from storage.
     *
     * @param \App\Http\Requests\Admin\ApproveWalletTransactionRequest $request
     * @param \App\Models\WalletTransaction $walletTransaction
     * @param \App\Services\WalletService $walletService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function approve(
        ApproveWalletTransactionRequest $request,
        WalletTransaction $walletTransaction,
        WalletService $walletService
    ): Response {
        $walletTransaction = $walletService->approve(
            $request->user(),
            $walletTransaction,
            $request->note,
            $request->file('receipt')
        );

        return ResponseBuilder::asSuccess()
            ->withMessage('Wallet transaction approved successfully')
            ->withData([
                'wallet_transaction' => $walletTransaction,
            ])
            ->build();
    }
}
