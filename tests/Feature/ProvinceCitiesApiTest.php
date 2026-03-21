<?php

use App\Models\City;
use App\Models\Country;
use App\Models\Province;
use App\Models\User;

test('guests cannot fetch cities for a province', function () {
    $province = Province::factory()->create();

    $this->getJson(route('api.provinces.cities', $province))
        ->assertUnauthorized();
});

test('verified user can fetch cities for a province as json', function () {
    $user = User::factory()->create();
    $province = Province::factory()->create();
    City::factory()->count(2)->create(['province' => $province->id]);

    $response = $this->actingAs($user)
        ->getJson(route('api.provinces.cities', $province));

    $response->assertOk()
        ->assertJsonCount(2)
        ->assertJsonStructure([
            '*' => ['id', 'name', 'name_en'],
        ]);
});

test('country province and city factories create valid hierarchy', function () {
    $country = Country::factory()->create();
    $province = Province::factory()->create(['country' => $country->id]);
    $city = City::factory()->create(['province' => $province->id]);

    expect($province->country)->toBe($country->id)
        ->and($city->province)->toBe($province->id);
});
