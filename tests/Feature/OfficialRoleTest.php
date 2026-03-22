<?php

use App\Models\Country;
use App\Models\OfficialRole;
use App\Models\Question;
use App\Models\User;
use Database\Seeders\OfficialRoleSeeder;

test('official role generates slug and keeps geographic scope nullable', function () {
    $country = Country::factory()->create();
    $role = OfficialRole::factory()->create([
        'name' => 'City Council',
        'country_id' => $country->id,
        'province_id' => null,
        'city_id' => null,
    ]);

    expect($role->slug)->toBe('city-council')
        ->and($role->country?->is($country))->toBeTrue()
        ->and($role->province)->toBeNull()
        ->and($role->city)->toBeNull();
});

test('users can be assigned to official roles', function () {
    $user = User::factory()->create();
    $role = OfficialRole::factory()->create();

    $user->officialRoles()->attach($role);

    expect($user->officialRoles()->pluck('official_roles.id')->all())
        ->toContain($role->id)
        ->and($role->users()->pluck('users.id')->all())->toContain($user->id);
});

test('question belongs to an official role', function () {
    $role = OfficialRole::factory()->create();
    $question = Question::factory()->create([
        'official_role_id' => $role->id,
    ]);

    expect($question->officialRole?->is($role))->toBeTrue()
        ->and($role->questions()->pluck('questions.id')->all())->toContain($question->id);
});

test('official role seeder creates all baseline government roles', function () {
    $this->seed(OfficialRoleSeeder::class);

    expect(OfficialRole::query()->count())->toBe(11)
        ->and(OfficialRole::query()->where('name', 'President')->exists())->toBeTrue()
        ->and(OfficialRole::query()->where('name', 'Mayor')->exists())->toBeTrue();
});
