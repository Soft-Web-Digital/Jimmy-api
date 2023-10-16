<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdminRequest extends FormRequest
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
            'country_id' => [
                'nullable',
                Rule::exists('countries', 'id')->whereNull('deleted_at'),
            ],
            'firstname' => 'required|string|max:191',
            'lastname' => 'required|string|max:191',
            'email' => [
                'required',
                Rule::when(app()->environment('production'), 'email:rfc,dns', 'email'),
                Rule::unique('admins', 'email'),
            ],
            'login_url' => [
                'nullable',
                Rule::when(app()->environment() === 'production', 'active_url', 'url'),
            ],
        ];
    }
}
