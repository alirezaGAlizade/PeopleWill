<?php

namespace Database\Seeders;

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
            'President',
            'First Vice President',
            'Minister',
            'Head of Key Independent National Institution',
            'Member of Parliament',
            'Governor (Elected)',
            'Governor (Appointed)',
            'Provincial Council',
            'Mayor',
            'City Council',
            'County Governor',
        ];

        foreach ($roles as $roleName) {
            OfficialRole::query()->updateOrCreate(
                ['name' => $roleName, 'country_id' => $countryId],
                [
                    'province_id' => null,
                    'city_id' => null,
                ],
            );
        }
    }
}
