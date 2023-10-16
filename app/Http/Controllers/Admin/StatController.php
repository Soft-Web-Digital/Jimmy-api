<?php

namespace App\Http\Controllers\Admin;

use App\DataTransferObjects\TransactionFilterData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FetchStatRequest;
use App\Services\Transaction\Admin\TransactionService;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response;

class StatController extends Controller
{
    /**
     * Get the overall system transactions.
     *
     * @param \App\Http\Requests\Admin\FetchStatRequest $request
     * @param \App\Services\Transaction\Admin\TransactionService $transactionService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function transactions(FetchStatRequest $request, TransactionService $transactionService): Response
    {
        $transactionFilterData = (new TransactionFilterData())
            ->setUserId($request->user_id)
            ->setCreationDate($request->creation_date);

        $data = [
            'giftcards' => $transactionService->giftcardStats($transactionFilterData)->get(),
            'asset_transactions' => $transactionService->assetTransactionStats($transactionFilterData)->get(),
            'wallet_transactions' => $transactionService->walletTransactionStats($transactionFilterData)->get(),
        ];

        return ResponseBuilder::asSuccess()
            ->withMessage('Transactions fetched successfully')
            ->withData($data)
            ->build();
    }
}
