<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ApproveGiftcardRequest extends FormRequest
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
            'complete_approval' => 'required|boolean',
            'review_amount' => 'nullable|numeric',
            'review_note' => [
                'nullable',
                'string',
            ],
            'review_proof' => [
                'nullable',
                'array',
            ],
            'review_proof.*' => [
                'url'
            ],
        ];
    }
}
