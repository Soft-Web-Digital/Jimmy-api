<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSystemBankAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function authorize(): bool|\Illuminate\Auth\Access\Response
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'bank_name' => 'required|string',
            'account_name' => 'required|string',
            'account_number' => [
                'required',
                'string',
                Rule::unique('system_bank_accounts')
                    ->where('bank_name', $this->bank_name)
                    ->where('account_name', $this->account_name)
                    ->ignoreModel($this->systemBankAccount)
            ],
        ];
    }
}
