<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class UpdateAssetRequest extends FormRequest
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
                'sometimes',
                'string',
                Rule::unique('assets')->ignoreModel($this->asset),
            ],
            'name' => [
                'sometimes',
                'string',
                Rule::unique('assets')->ignoreModel($this->asset),
            ],
            'icon' => [
                'sometimes',
                Rule::excludeIf(filter_var($this->icon, FILTER_VALIDATE_URL) !== true),
                File::image()->max(2 * 1024),
            ],
            'buy_rate' => [
                'sometimes',
                'numeric',
            ],
            'sell_rate' => [
                'sometimes',
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
                'nullable',
                'numeric',
            ],
            'buy_max_amount' => [
                'nullable',
                'numeric',
            ],
            'sell_min_amount' => [
                'nullable',
                'numeric',
            ],
            'sell_max_amount' => [
                'nullable',
                'numeric',
            ],
        ];
    }
}
