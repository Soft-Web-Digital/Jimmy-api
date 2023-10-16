<?php

namespace App\Http\Controllers\User;

use App\DataTransferObjects\TransactionFilterData;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\FetchStatRequest;
use App\Services\Transaction\User\TransactionService;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response;

class StatController extends Controller
{
    /**
     * Get the user transactions.
     *
     * @param \App\Http\Requests\User\FetchStatRequest $request
     * @param \App\Services\Transaction\User\TransactionService $transactionService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function transactions(FetchStatRequest $request, TransactionService $transactionService): Response
    {
        $transactionFilterData = (new TransactionFilterData())
            ->setUserId($request->user()->id)
            ->setPerPage((int) $request->per_page)
            ->setCreationDate($request->creation_date)
            ->setPayableAmount($request->payable_amount)
            ->setStatus($request->status);

        $data = [
            'stats' => $transactionService->assetTransactionStats($transactionFilterData)
                ->unionAll($transactionService->giftcardStats($transactionFilterData))
                ->get(),
            'records' => $transactionService->assetTransactionRecords($transactionFilterData)
                ->unionAll($transactionService->giftcardRecords($transactionFilterData))
                ->orderByDesc('created_at')
                ->paginate($transactionFilterData->getPerPage() ?: 10)
        ];

        return ResponseBuilder::asSuccess()
            ->withMessage('Transactions fetched successfully')
            ->withData($data)
            ->build();
    }
}
