<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGiftcardProductRequest extends FormRequest
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
            'giftcard_category_id' => [
                'required',
                'string',
                Rule::exists('giftcard_categories', 'id')->whereNotNull('sale_activated_at')->whereNull('deleted_at'),
            ],
            'country_id' => [
                'required',
                Rule::exists('giftcard_category_country')->where('giftcard_category_id', $this->giftcard_category_id),
            ],
            'currency_id' => [
                'required',
                Rule::exists('currencies', 'id'),
            ],
            'name' => [
                'required',
                'string',
                'max:191',
                Rule::unique('giftcard_products')
                    ->where('giftcard_category_id', $this->giftcard_category_id)
                    ->where('country_id', $this->country_id)
                    ->where('currency_id', $this->currency_id),
            ],
            'sell_rate' => [
                'required',
                'numeric',
                'min:1',
            ],
            'sell_min_amount' => [
                'required',
                'numeric',
                'min:0',
            ],
            'sell_max_amount' => [
                'required',
                'numeric',
                'gte:sell_min_amount',
            ],
        ];
    }
}
