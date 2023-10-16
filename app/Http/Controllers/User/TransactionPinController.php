<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Auth\ResetTransactionPinRequest;
use App\Http\Requests\User\Auth\UpdateTransactionPinRequest;
use App\Services\Auth\TransactionPinService;
use Illuminate\Http\Request;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response;

class TransactionPinController extends Controller
{
    /**
     * Update transaction pin.
     *
     * @param \App\Http\Requests\User\Auth\UpdateTransactionPinRequest $request
     * @param \App\Services\Auth\TransactionPinService $transactionPinService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function update(UpdateTransactionPinRequest $request, TransactionPinService $transactionPinService): Response
    {
        /** @var \App\Models\User $user */
        $user = $request->user('api_user');

        $transactionPinService->update($user, $request->new_pin);

        return ResponseBuilder::asSuccess()
            ->withMessage('Transaction PIN updated successfully.')
            ->build();
    }

    /**
     * Request a reset code.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Services\Auth\TransactionPinService $transactionPinService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function requestReset(Request $request, TransactionPinService $transactionPinService): Response
    {
        /** @var \App\Models\User $user */
        $user = $request->user('api_user');

        $transactionPinService->requestResetCode($user);

        return ResponseBuilder::asSuccess()
            ->withMessage('Transaction PIN reset code sent successfully.')
            ->build();
    }

    /**
     * Reset transaction pin.
     *
     * @param \App\Http\Requests\User\Auth\ResetTransactionPinRequest $request
     * @param \App\Services\Auth\TransactionPinService $transactionPinService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function reset(ResetTransactionPinRequest $request, TransactionPinService $transactionPinService): Response
    {
        /** @var \App\Models\User $user */
        $user = $request->user('api_user');

        $transactionPinService->reset($user, $request->code, $request->pin);

        return ResponseBuilder::asSuccess()
            ->withMessage('Transaction PIN reset successfully.')
            ->build();
    }

    /**
     * Toggle the activation of the usage of transaction pin.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Services\Auth\TransactionPinService $transactionPinService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toggleActivation(Request $request, TransactionPinService $transactionPinService): Response
    {
        /** @var \App\Models\User $user */
        $user = $request->user('api_user');

        $status = $transactionPinService->toggleActivation($user);

        return ResponseBuilder::asSuccess()
            ->withMessage('Transaction PIN activation toggled successfully.')
            ->withData([
                'status' => $status,
            ])
            ->build();
    }
}
