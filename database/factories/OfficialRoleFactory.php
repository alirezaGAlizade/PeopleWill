<?php

namespace Database\Factories;

use App\Models\OfficialRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OfficialRole>
 */
class OfficialRoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->jobTitle(),
            'country_id' => null,
            'province_id' => null,
            'city_id' => null,
        ];
    }
}
