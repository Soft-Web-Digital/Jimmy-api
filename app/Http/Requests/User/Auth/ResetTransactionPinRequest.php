<?php

namespace App\Http\Requests\User\Auth;

use App\Rules\CompromisedTransactionPinRule;
use Illuminate\Foundation\Http\FormRequest;

class ResetTransactionPinRequest extends FormRequest
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
            'code' => 'required|string',
            'pin' => [
                'required',
                'digits:4',
                new CompromisedTransactionPinRule(),
                'confirmed',
            ],
        ];
    }
}
