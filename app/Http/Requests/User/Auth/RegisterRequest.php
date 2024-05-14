<?php

namespace App\Http\Requests\User\Auth;

use App\Models\Country;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    private string|null $countryCode;

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
        $this->countryCode = Country::query()
            ->where('id', $this->country_id)
            ->whereNotNull('registration_activated_at')
            ->value('alpha2_code');

        return [
            'country_id' => [
                'required',
                Rule::exists('countries', 'id')->whereNotNull('registration_activated_at')->whereNull('deleted_at'),
            ],
            'firstname' => 'required|string|max:191',
            'lastname' => 'required|string|max:191',
            'email' => [
                'required',
                Rule::when(app()->environment('production'), 'email:rfc,dns', 'email'),
                Rule::unique('users', 'email'),
            ],
            'password' => [
                'required',
                'confirmed',
            ],
            'username' => [
                'required',
                'string',
                'max:20',
                Rule::unique('users', 'username'),
            ],
            'phone_number' => [
                'required',
                Rule::phone()->country($this->countryCode),
            ],
            'ref' => [
                'nullable',
                Rule::exists('users', 'ref_code')
            ],
        ];
    }
}
