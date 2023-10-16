<?php

namespace App\Http\Requests\User;

use App\Enums\AssetTransactionTradeType;
use App\Rules\TransactionPinRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreAssetTransactionRequest extends FormRequest
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
            'network_id' => [
                'required',
                Rule::exists('networks', 'id')->whereNull('deleted_at'),
            ],
            'asset_id' => [
                'required',
                Rule::exists('assets', 'id')->whereNull('deleted_at'),
                Rule::exists('asset_network')->where('network_id', $this->network_id),
            ],
            'asset_amount' => [
                'required',
                'numeric',
            ],
            'wallet_address' => [
                'nullable',
                Rule::excludeIf($this->trade_type != AssetTransactionTradeType::BUY->value),
                Rule::requiredIf($this->trade_type == AssetTransactionTradeType::BUY->value),
                'string',
                'max:50',
                'confirmed',
            ],
            'user_bank_account_id' => [
                'nullable',
                Rule::excludeIf($this->trade_type != AssetTransactionTradeType::SELL->value),
                Rule::requiredIf($this->trade_type == AssetTransactionTradeType::SELL->value),
                Rule::exists('user_bank_accounts', 'id')->where('user_id', $this->user()->id),
            ],
            'comment' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'transaction_pin' => [
                Rule::requiredIf((bool) $this->user()->transaction_pin_activated_at),
                new TransactionPinRule(),
            ],
        ];
    }
}
