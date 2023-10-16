<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
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
            'name' => [
                'sometimes',
                'string',
                'max:20',
                Rule::unique('roles')->where('guard_name', 'api_admin')->ignoreModel($this->role),
            ],
            'description' => 'nullable|string|max:255',
            'permissions' => 'nullable|array|min:1',
            'permissions.*' => [
                Rule::exists('permissions', 'id')->where('guard_name', 'api_admin'),
            ],
        ];
    }
}
