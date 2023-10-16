<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class FinanceUserRequest extends FormRequest
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
            'amount' => 'required|numeric|min:10',
            'note' => 'nullable|string',
            'receipt' => [
                'nullable',
                File::default(),
            ],
        ];
    }
}
