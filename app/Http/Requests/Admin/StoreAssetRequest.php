<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class StoreAssetRequest extends FormRequest
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
            'code' => [
                'required',
                'string',
                Rule::unique('assets'),
            ],
            'name' => [
                'required',
                'string',
                Rule::unique('assets'),
            ],
            'icon' => [
                'required',
                File::image()->max(2 * 1024),
            ],
            'buy_rate' => [
                'required',
                'numeric',
            ],
            'sell_rate' => [
                'required',
                'numeric',
            ],
            'networks' => [
                'nullable',
                'array',
            ],
            'networks.*' => [
                Rule::exists('networks', 'id')->whereNull('deleted_at'),
            ],
            'buy_min_amount' => [
                'required',
                'numeric',
            ],
            'buy_max_amount' => [
                'required',
                'numeric',
            ],
            'sell_min_amount' => [
                'required',
                'numeric',
            ],
            'sell_max_amount' => [
                'required',
                'numeric',
            ],
        ];
    }
}
