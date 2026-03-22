<?php

use App\Models\City;
use App\Models\Province;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyFeature(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $province = Province::factory()->create();
    $city = City::factory()->create(['province' => $province->id]);

    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'country_id' => $province->country,
        'province_id' => $province->id,
        'city_id' => $city->id,
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('home', absolute: false));
});
