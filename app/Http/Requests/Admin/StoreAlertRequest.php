<?php

namespace App\Http\Requests\Admin;

use App\Enums\AlertChannel;
use App\Enums\AlertTargetUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreAlertRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'target_user' => [
                'required',
                new Enum(AlertTargetUser::class),
            ],
            'dispatch_datetime' => 'required|date|after_or_equal:now + 5 minutes',
            'channels' => 'required|array|min:1',
            'channels.*' => [
                new Enum(AlertChannel::class),
            ],
            'users' => [
                'nullable',
                Rule::excludeIf($this->target_user !== AlertTargetUser::SPECIFIC->value),
                Rule::requiredIf($this->target_user === AlertTargetUser::SPECIFIC->value),
                'array',
                'min:1',
            ],
            'users.*' => [
                Rule::exists('users', 'id')->whereNull('deleted_at'),
            ],
        ];
    }
}
