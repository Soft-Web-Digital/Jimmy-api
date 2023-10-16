<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class StoreGiftcardCategoryRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                Rule::unique('giftcard_categories'),
            ],
            'icon' => [
                'nullable',
                File::image()->max(2 * 1024),
            ],
            'sale_term' => 'nullable|string|max:5000',
            'countries' => 'nullable|array',
            'countries.*' => [
                Rule::exists('countries', 'id')->whereNotNull('giftcard_activated_at')->whereNull('deleted_at'),
            ],
            'admins' => 'nullable|array',
            'admins.*' => [
                Rule::exists('admins', 'id')->whereNull('deleted_at'),
            ],
        ];
    }
}
