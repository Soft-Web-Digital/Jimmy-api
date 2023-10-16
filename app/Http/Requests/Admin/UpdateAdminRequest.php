<?php

namespace App\Http\Requests\Admin;

use App\Models\Country;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminRequest extends FormRequest
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
                'sometimes',
                'nullable',
                Rule::exists('countries', 'id')->whereNull('deleted_at'),
            ],
            'firstname' => 'sometimes|string|max:191',
            'lastname' => 'sometimes|string|max:191',
            'email' => [
                'sometimes',
                Rule::when(app()->environment('production'), 'email:rfc,dns', 'email'),
                Rule::unique('admins', 'email')->ignoreModel($this->admin),
            ],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (
                !is_null($this->country_id) &&
                $this->country_id !== $this->admin->country_id &&
                !is_null($this->admin->phone_number)
            ) {
                try {
                    $countryCode = Country::query()
                        ->where('id', $this->country_id ?? $this->admin->country_id)
                        ->value('alpha2_code');

                    phone($this->admin->phone_number, $countryCode)->isOfCountry($countryCode);
                } catch (\Propaganistas\LaravelPhone\Exceptions\NumberParseException) {
                    $validator->errors()->add('country_id', trans('validation.phone_country', [
                        'attribute' => 'country id',
                        'other' => 'phone number',
                    ]));
                }
            }
        });
    }
}
