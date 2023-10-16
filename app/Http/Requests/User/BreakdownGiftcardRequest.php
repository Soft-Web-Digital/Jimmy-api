<?php

namespace App\Http\Requests\User;

use App\Enums\GiftcardTradeType;
use App\Models\GiftcardProduct;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class BreakdownGiftcardRequest extends FormRequest
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
        /** @var \App\Models\GiftcardProduct|null $giftcardProduct */
        $giftcardProduct = GiftcardProduct::query()->where('id', $this->giftcard_product_id)->first();

        return [
            'giftcard_product_id' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (
                        GiftcardProduct::activated()
                            ->where('id', $value)
                            ->whereHas(
                                'giftcardCategory',
                                fn ($query) => $query->whereNotNull('sale_activated_at')
                            )
                            ->doesntExist()
                    ) {
                        $fail(trans('validation.exists'));
                    }
                },
            ],
            'trade_type' => [
                'required',
                new Enum(GiftcardTradeType::class),
            ],
            'amount' => [
                'required',
                'numeric',
                'min:' . ($giftcardProduct?->sell_min_amount ?? 0),
                'max:' . ($giftcardProduct?->sell_max_amount ?? 0),
            ],
        ];
    }
}
