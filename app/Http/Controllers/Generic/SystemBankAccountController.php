<?php

namespace App\Http\Controllers\Generic;

use App\Http\Controllers\Controller;
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
     * Handle the incoming request.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function __invoke(): Response
    {
        $systemBankAccounts = $this->systemBankAccount->all();

        return ResponseBuilder::asSuccess()
            ->withMessage('System bank accounts fetched successfully.')
            ->withData([
                'system_bank_accounts' => $systemBankAccounts,
            ])
            ->build();
    }
}
