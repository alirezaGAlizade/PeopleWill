<?php

namespace Database\Factories;

use App\Enums\MandatoryResponseThresholdPercent;
use App\Enums\WindowDuration;
use App\Enums\WindowPlan;
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
            'window_plan' => WindowPlan::Continuously,
            'open_window_duration' => WindowDuration::SevenDays,
            'last_window_close_date' => now(),
            'mandatory_response_threshold' => MandatoryResponseThresholdPercent::Percent5,
            'response_deadline_days' => 14,
            'participation_quorum_percent' => 10,
            'response_rejection_downvote_percent' => 10,
        ];
    }
}
