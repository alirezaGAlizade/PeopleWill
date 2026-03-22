<?php

namespace App\Concerns;

use App\Models\City;
use App\Models\Province;
use App\Models\User;
use Illuminate\Validation\Rule;

trait ProfileValidationRules
{
    /**
     * Get the validation rules used to validate user profiles.
     *
     * @return array<string, array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>>
     */
    protected function profileRules(?int $userId = null): array
    {
        return [
            'name' => $this->nameRules(),
            'email' => $this->emailRules($userId),
            ...$this->geographyRules(),
        ];
    }

    /**
     * @return array<string, array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>>
     */
    protected function geographyRules(): array
    {
        return [
            'country_id' => ['required', 'integer', 'exists:countries,id'],
            'province_id' => ['required', 'integer', 'exists:provinces,id'],
            'city_id' => ['required', 'integer', 'exists:cities,id'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  callable(string, string): void  $addError
     */
    protected function validateGeographyConsistency(array $data, callable $addError): void
    {
        $countryId = isset($data['country_id']) ? (int) $data['country_id'] : null;
        $provinceId = isset($data['province_id']) ? (int) $data['province_id'] : null;
        $cityId = isset($data['city_id']) ? (int) $data['city_id'] : null;

        if ($countryId === null || $provinceId === null || $cityId === null) {
            return;
        }

        $province = Province::query()->find($provinceId);

        if ($province === null) {
            return;
        }

        if ((int) $province->country !== $countryId) {
            $addError('province_id', 'The selected province does not belong to the selected country.');
        }

        $city = City::query()->find($cityId);

        if ($city === null) {
            return;
        }

        if ((int) $city->province !== $provinceId) {
            $addError('city_id', 'The selected city does not belong to the selected province.');
        }
    }

    /**
     * Get the validation rules used to validate user names.
     *
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    protected function nameRules(): array
    {
        return ['required', 'string', 'max:255'];
    }

    /**
     * Get the validation rules used to validate user emails.
     *
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    protected function emailRules(?int $userId = null): array
    {
        return [
            'required',
            'string',
            'email',
            'max:255',
            $userId === null
                ? Rule::unique(User::class)
                : Rule::unique(User::class)->ignore($userId),
        ];
    }
}
