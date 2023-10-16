<?php

namespace App\Http\Requests\User;

use App\Rules\TransactionPinRule;
use App\Rules\WalletBalanceRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWalletWithdrawalRequest extends FormRequest
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
            'user_bank_account_id' => [
                'required',
                Rule::exists('user_bank_accounts', 'id')->where('user_id', $this->user()->id),
            ],
            'amount' => [
                'required',
                'numeric',
                new WalletBalanceRule($this->user()),
            ],
            'comment' => 'nullable|string|max:2000',
            'transaction_pin' => [
                Rule::requiredIf((bool) $this->user()->transaction_pin_activated_at),
                new TransactionPinRule(),
            ],
        ];
    }
}
