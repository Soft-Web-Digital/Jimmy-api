<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserBankAccountRequest;
use App\Models\UserBankAccount;
use App\Services\Profile\User\UserBankAccountService;
use Illuminate\Http\Request;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class UserBankAccountController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param \App\Models\UserBankAccount $userBankAccount
     */
    public function __construct(public UserBankAccount $userBankAccount)
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
        $userBankAccounts = QueryBuilder::for(
            $this->userBankAccount->query()->whereBelongsTo($request->user(), 'user')
        )
            ->with('bank:id,name')
            ->get();

        return ResponseBuilder::asSuccess()
            ->withMessage('Bank accounts fetched successfully.')
            ->withData([
                'bank_accounts' => $userBankAccounts,
            ])
            ->build();
    }

    /**
     * Verify account details.
     *
     * @param \App\Http\Requests\User\StoreUserBankAccountRequest $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function verify(
        StoreUserBankAccountRequest $request,
        UserBankAccountService $userBankAccountService
    ): Response {
        $userBankAccount = $userBankAccountService->verify($request->bank_id, $request->account_number);

        return ResponseBuilder::asSuccess()
            ->withMessage('Bank account verified successfully.')
            ->withData([
                'bank_account' => $userBankAccount->toArray(),
            ])
            ->build();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \App\Http\Requests\User\StoreUserBankAccountRequest $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function store(
        StoreUserBankAccountRequest $request,
        UserBankAccountService $userBankAccountService
    ): Response {
        $userBankAccount = $userBankAccountService->store(
            $request->user(),
            $request->bank_id,
            $request->account_number
        );

        return ResponseBuilder::asSuccess()
            ->withHttpCode(201)
            ->withMessage('Bank account created successfully')
            ->withData([
                'bank_account' => $userBankAccount,
            ])
            ->build();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\UserBankAccount $userBankAccount
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(UserBankAccount $userBankAccount): Response
    {
        $this->authorize('delete', $userBankAccount);

        $userBankAccount->deleteOrFail();

        return response()->json(null, 204);
    }
}
