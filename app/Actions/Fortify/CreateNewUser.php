<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->after(function (\Illuminate\Validation\Validator $validator) use ($input): void {
            $this->validateGeographyConsistency($input, function (string $key, string $message) use ($validator): void {
                $validator->errors()->add($key, $message);
            });
        })->validate();

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'country_id' => (int) $input['country_id'],
            'province_id' => (int) $input['province_id'],
            'city_id' => (int) $input['city_id'],
        ]);
    }
}
