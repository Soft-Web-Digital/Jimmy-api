<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSystemBankAccountRequest;
use App\Http\Requests\Admin\UpdateSystemBankAccountRequest;
use App\Models\SystemBankAccount;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response;

class SystemBankAccountController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param \App\Models\SystemBankAccount $systemBankAccount
     */
    public function __construct(public SystemBankAccount $systemBankAccount)
    {
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index(): Response
    {
        $systemBankAccounts = $this->systemBankAccount->all();

        return ResponseBuilder::asSuccess()
            ->withMessage('System bank accounts fetched successfully')
            ->withData([
                'system_bank_accounts' => $systemBankAccounts,
            ])
            ->build();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \App\Http\Requests\Admin\StoreSystemBankAccountRequest $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function store(StoreSystemBankAccountRequest $request): Response
    {
        $systemBankAccount = $this->systemBankAccount->query()->create($request->validated());

        return ResponseBuilder::asSuccess()
            ->withHttpCode(201)
            ->withMessage('System bank account created successfully')
            ->withData([
                'system_bank_account' => $systemBankAccount->refresh(),
            ])
            ->build();
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\SystemBankAccount $systemBankAccount
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function show(SystemBankAccount $systemBankAccount): Response
    {
        return ResponseBuilder::asSuccess()
            ->withMessage('System bank account fetched successfully')
            ->withData([
                'system_bank_account' => $systemBankAccount,
            ])
            ->build();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \App\Http\Requests\Admin\UpdateSystemBankAccountRequest $request
     * @param \App\Models\SystemBankAccount $systemBankAccount
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function update(UpdateSystemBankAccountRequest $request, SystemBankAccount $systemBankAccount): Response
    {
        $systemBankAccount->updateOrFail($request->validated());

        return ResponseBuilder::asSuccess()
            ->withMessage('System bank account updated successfully')
            ->withData([
                'system_bank_account' => $systemBankAccount,
            ])
            ->build();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\SystemBankAccount $systemBankAccount
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(SystemBankAccount $systemBankAccount): Response
    {
        $systemBankAccount->deleteOrFail();

        return response()->json(null, 204);
    }
}
