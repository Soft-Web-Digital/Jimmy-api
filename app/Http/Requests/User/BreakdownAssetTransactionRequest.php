<?php

namespace App\Http\Requests\User;

use App\Enums\AssetTransactionTradeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class BreakdownAssetTransactionRequest extends FormRequest
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
            'trade_type' => [
                'required',
                new Enum(AssetTransactionTradeType::class),
            ],
            'asset_id' => [
                'required',
                Rule::exists('assets', 'id')->whereNull('deleted_at'),
            ],
            'asset_amount' => [
                'required',
                'numeric',
            ],
        ];
    }
}
