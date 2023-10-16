<?php

namespace App\Http\Requests\User;

use App\Enums\GiftcardCardType;
use App\Models\GiftcardProduct;
use App\Rules\TransactionPinRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\File;

class StoreGiftcardSaleRequest extends FormRequest
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
            'user_bank_account_id' => [
                'required',
                Rule::exists('user_bank_accounts', 'id')->where('user_id', $this->user()->id),
            ],
            'card_type' => [
                'required',
                new Enum(GiftcardCardType::class),
            ],
            'upload_type' => [
                'required',
                'in:code,media',
            ],
            'amount' => [
                'required',
                'numeric',
                'min:' . ($giftcardProduct?->sell_min_amount ?? 0),
                'max:' . ($giftcardProduct?->sell_max_amount ?? 0),
            ],
            'quantity' => [
                'nullable',
                'integer',
            ],
            'comment' => [
                'nullable',
                'string',
                'max:255',
            ],
            'codes' => [
                'nullable',
                'exclude_unless:upload_type,code',
                Rule::requiredIf($this->upload_type === 'code'),
                'array',
                'size:' . (int) ($this->quantity ?? 1),
            ],
            'codes.*' => 'string',
            'pins' => [
                'nullable',
                'exclude_unless:upload_type,code',
                Rule::requiredIf($this->upload_type === 'code'),
                'array',
                'size:' . (int) ($this->quantity ?? 1),
            ],
            'pins.*' => 'string',
            'cards' => [
                'nullable',
                'exclude_unless:upload_type,media',
                Rule::requiredIf($this->upload_type === 'media'),
                'array',
                'min:' . (int) ($this->quantity ?? 1),
            ],
            'cards.*' => [
                'url',
//                File::image()->max(10 * 1024),
            ],
            'transaction_pin' => [
                Rule::requiredIf((bool) $this->user()->transaction_pin_activated_at),
                new TransactionPinRule(),
            ],
            'group_tag' => [
                'nullable',
                'string',
                'max:30',
            ],
        ];
    }
}
