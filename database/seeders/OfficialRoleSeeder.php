<?php

namespace Database\Seeders;

use App\Enums\MandatoryResponseThresholdPercent;
use App\Enums\WindowDuration;
use App\Enums\WindowPlan;
use App\Models\Country;
use App\Models\OfficialRole;
use Illuminate\Database\Seeder;

class OfficialRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $countryId = Country::query()->value('id');

        $roles = [
            'President' => [WindowPlan::Every6Months, MandatoryResponseThresholdPercent::Percent3],
            'First Vice President' => [WindowPlan::Every4Months, MandatoryResponseThresholdPercent::Percent5],
            'Minister' => [WindowPlan::Every3Months, MandatoryResponseThresholdPercent::Percent4],
            'Head of Key Independent National Institution' => [WindowPlan::Every4Months, MandatoryResponseThresholdPercent::Percent4],
            'Member of Parliament' => [WindowPlan::Every2Months, MandatoryResponseThresholdPercent::Percent6],
            'Governor (Elected)' => [WindowPlan::Continuously, MandatoryResponseThresholdPercent::Percent5],
            'Governor (Appointed)' => [WindowPlan::Continuously, MandatoryResponseThresholdPercent::Percent5],
            'Provincial Council' => [WindowPlan::Continuously, MandatoryResponseThresholdPercent::Percent5],
            'Mayor' => [WindowPlan::Continuously, MandatoryResponseThresholdPercent::Percent4],
            'City Council' => [WindowPlan::Continuously, MandatoryResponseThresholdPercent::Percent5],
            'County Governor' => [WindowPlan::Continuously, MandatoryResponseThresholdPercent::Percent6],
        ];

        foreach ($roles as $roleName => [$windowPlan, $threshold]) {
            OfficialRole::query()->updateOrCreate(
                ['name' => $roleName, 'country_id' => $countryId],
                [
                    'province_id' => null,
                    'city_id' => null,
                    'window_plan' => $windowPlan,
                    'open_window_duration' => WindowDuration::SevenDays,
                    'last_window_close_date' => now(),
                    'mandatory_response_threshold' => $threshold,
                    'response_deadline_days' => 7,
                    'participation_quorum_percent' => 10,
                    'response_rejection_downvote_percent' => 10,
                ],
            );
        }
    }
}
