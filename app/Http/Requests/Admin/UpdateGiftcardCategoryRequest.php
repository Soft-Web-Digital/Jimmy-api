<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class UpdateGiftcardCategoryRequest extends FormRequest
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
                'sometimes',
                'string',
                Rule::excludeIf((bool) $this->giftcardCategory->service_provider),
                Rule::unique('giftcard_categories')->ignoreModel($this->giftcardCategory),
            ],
            'icon' => [
                'nullable',
                File::image()->max(2 * 1024),
            ],
            'sale_term' => 'nullable|string|max:5000',
            'purchase_term' => 'nullable|string|max:5000',
            'countries' => [
                'nullable',
                'array',
                Rule::excludeIf((bool) $this->giftcardCategory->service_provider),
            ],
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
