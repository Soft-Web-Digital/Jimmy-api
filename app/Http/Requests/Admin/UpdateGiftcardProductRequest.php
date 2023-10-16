<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGiftcardProductRequest extends FormRequest
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
                'sometimes',
                'string',
                Rule::excludeIf((bool) $this->giftcardProduct->service_provider),
                Rule::exists('giftcard_categories', 'id'),
            ],
            'country_id' => [
                'sometimes',
                Rule::excludeIf((bool) $this->giftcardProduct->service_provider),
                Rule::exists('giftcard_category_country')->where('giftcard_category_id', $this->giftcard_category_id),
            ],
            'currency_id' => [
                'sometimes',
                Rule::excludeIf((bool) $this->giftcardProduct->service_provider),
                Rule::exists('currencies', 'id'),
            ],
            'name' => [
                'sometimes',
                'string',
                'max:191',
                Rule::excludeIf((bool) $this->giftcardProduct->service_provider),
                Rule::unique('giftcard_products')
                    ->where('giftcard_category_id', $this->giftcard_category_id)
                    ->where('country_id', $this->country_id)
                    ->where('currency_id', $this->currency_id)
                    ->ignoreModel($this->giftcardProduct),
            ],
            'sell_rate' => [
                'sometimes',
                'numeric',
                'min:1',
            ],
            'sell_min_amount' => [
                'sometimes',
                'numeric',
                'min:0',
                'lte:' . $this->giftcardProduct->sell_max_amount,
            ],
            'sell_max_amount' => [
                'sometimes',
                'numeric',
                Rule::when(
                    (bool) $this->sell_min_amount,
                    'gte:sell_min_amount',
                    'gte:' . $this->giftcardProduct->sell_min_amount
                )
            ],
        ];
    }
}
