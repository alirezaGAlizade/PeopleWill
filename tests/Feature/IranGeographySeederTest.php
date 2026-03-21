<?php

use Database\Seeders\IranGeographySeeder;
use Illuminate\Support\Facades\DB;

test('iran geography seeder loads bundled sql data once', function () {
    $seeder = new IranGeographySeeder;
    $seeder->run();

    expect(DB::table('countries')->count())->toBe(1)
        ->and(DB::table('provinces')->count())->toBe(31)
        ->and(DB::table('cities')->count())->toBe(469);

    $country = DB::table('countries')->where('id', 1)->first();
    expect($country)->not->toBeNull()
        ->and($country->capital_city)->toBe(87)
        ->and($country->name_en)->toBe('Iran');
});

test('iran geography seeder is idempotent', function () {
    $seeder = new IranGeographySeeder;
    $seeder->run();
    $seeder->run();

    expect(DB::table('cities')->count())->toBe(469);
});
