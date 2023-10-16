<?php

namespace App\Http\Requests\Admin;

use App\Enums\AlertChannel;
use App\Enums\AlertTargetUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateAlertRequest extends FormRequest
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
            'title' => 'sometimes|string|max:255',
            'body' => 'sometimes|string',
            'target_user' => [
                'sometimes',
                new Enum(AlertTargetUser::class),
            ],
            'dispatch_datetime' => 'sometimes|date|after_or_equal:now + 5 minutes',
            'channels' => 'sometimes|array|min:1',
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
