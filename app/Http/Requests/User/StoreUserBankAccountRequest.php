<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserBankAccountRequest extends FormRequest
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
            'bank_id' => [
                'required',
                Rule::exists('banks', 'id')->whereNull('deleted_at'),
            ],
            'account_number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('user_bank_accounts', 'account_number')
                    ->where('bank_id', $this->bank_id)
                    ->where('user_id', $this->user()->id)
            ],
        ];
    }
}
