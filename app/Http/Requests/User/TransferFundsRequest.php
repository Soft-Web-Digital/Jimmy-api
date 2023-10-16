<?php

namespace App\Http\Requests\User;

use App\Rules\TransactionPinRule;
use App\Rules\WalletBalanceRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class TransferFundsRequest extends FormRequest
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
            'amount' => [
                'required',
                'numeric',
                'min:10',
                new WalletBalanceRule($this->user()),
            ],
            'receipt' => [
                'nullable',
                File::default()->max(5 * 1024),
            ],
            'transaction_pin' => [
                Rule::requiredIf((bool) $this->user()->transaction_pin_activated_at),
                new TransactionPinRule(),
            ],
        ];
    }
}
