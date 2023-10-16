<?php

namespace App\Http\Requests\Admin\Profile;

use App\Enums\Permission;
use App\Models\Country;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class ProfileUpdateRequest extends FormRequest
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
            ->where('id', $this->country_id ?: $this->user()->country_id)
            ->value('alpha2_code');

        return [
            'country_id' => [
                'sometimes',
                Rule::exists('countries', 'id')->whereNull('deleted_at'),
            ],
            'firstname' => 'sometimes|string|max:191',
            'lastname' => 'sometimes|string|max:191',
            'email' => [
                'sometimes',
                // @phpstan-ignore-next-line
                Rule::excludeIf(!$this->user()->hasPermissionTo(Permission::MANAGE_ADMINS->value)),
                Rule::when(app()->environment('production'), 'email:rfc,dns', 'email'),
                Rule::unique('admins', 'email')->ignoreModel($this->user()),
            ],
            'avatar' => [
                'sometimes',
                Rule::excludeIf(filter_var($this->avatar, FILTER_VALIDATE_URL) !== true),
                File::image()->max(2 * 1024),
            ],
            'phone_number' => [
                'sometimes',
                Rule::phone()->country($this->countryCode),
            ],
            'fcm_token' => [
                'nullable',
                'string',
            ],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (
                !is_null($this->country_id) &&
                $this->country_id !== $this->user()->country_id &&
                $this->phone_number == null &&
                !is_null($this->user()->phone_number)
            ) {
                try {
                    phone($this->user()->phone_number, $this->countryCode)->isOfCountry($this->countryCode);
                } catch (\Propaganistas\LaravelPhone\Exceptions\NumberParseException) {
                    $validator->errors()->add('country_id', trans('validation.phone_country', [
                        'attribute' => 'country',
                        'other' => 'phone number',
                    ]));
                }
            }
        });
    }
}
