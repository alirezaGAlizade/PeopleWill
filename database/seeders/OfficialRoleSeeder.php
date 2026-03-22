<?php

namespace Database\Seeders;

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
            'President' => WindowPlan::Every6Months,
            'First Vice President' => WindowPlan::Every4Months,
            'Minister' => WindowPlan::Every3Months,
            'Head of Key Independent National Institution' => WindowPlan::Every4Months,
            'Member of Parliament' => WindowPlan::Every2Months,
            'Governor (Elected)' => WindowPlan::Continuously,
            'Governor (Appointed)' => WindowPlan::Continuously,
            'Provincial Council' => WindowPlan::Continuously,
            'Mayor' => WindowPlan::Continuously,
            'City Council' => WindowPlan::Continuously,
            'County Governor' => WindowPlan::Continuously,
        ];

        foreach ($roles as $roleName => $windowPlan) {
            OfficialRole::query()->updateOrCreate(
                ['name' => $roleName, 'country_id' => $countryId],
                [
                    'province_id' => null,
                    'city_id' => null,
                    'window_plan' => $windowPlan,
                    'open_window_duration' => WindowDuration::SevenDays,
                    'last_window_close_date' => now(),
                ],
            );
        }
    }
}
