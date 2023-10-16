<?php

namespace App\Http\Requests\User\Auth;

use App\Rules\CompromisedTransactionPinRule;
use App\Rules\TransactionPinRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransactionPinRequest extends FormRequest
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
            'old_pin' => [
                'nullable',
                Rule::when(
                    $this->user()->transaction_pin_set,
                    'required',
                    'exclude',
                ),
                'digits:4',
                new TransactionPinRule(),
            ],
            'new_pin' => [
                'required',
                'digits:4',
                'different:old_pin',
                new CompromisedTransactionPinRule(),
                'confirmed',
            ],
        ];
    }
}
