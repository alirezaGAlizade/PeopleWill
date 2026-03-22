<?php

use App\Enums\EffectiveArea;
use App\Enums\QuestionStatus;
use App\Enums\WindowDuration;
use App\Enums\WindowPlan;
use App\Models\City;
use App\Models\OfficialRole;
use App\Models\Province;
use App\Models\Question;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->officialRole = OfficialRole::factory()->create();
});

test('guests cannot store questions', function () {
    $this->post(route('questions.store'), [
        'body' => 'What is the meaning of life?',
    ])->assertRedirect(route('login'));
});

test('authenticated user can store a draft question and is redirected to edit', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('questions.store'), [
            'body' => 'What is the meaning of life?',
        ]);

    $question = Question::query()->where('user_id', $user->id)->first();

    expect($question)->not->toBeNull()
        ->and($question->status)->toBe(QuestionStatus::Incomplete)
        ->and($question->body)->toBe('What is the meaning of life?');

    $response->assertRedirect(route('questions.edit', $question));

    $this->assertDatabaseHas('questions', [
        'user_id' => $user->id,
        'body' => 'What is the meaning of life?',
        'status' => QuestionStatus::Incomplete->value,
    ]);
});

test('question store validates body', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('questions.store'), [
            'body' => '',
        ])
        ->assertSessionHasErrors('body');
});

test('question store rejects body over max length', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('questions.store'), [
            'body' => str_repeat('a', 1001),
        ])
        ->assertSessionHasErrors('body');
});

test('questions support soft delete', function () {
    $question = Question::factory()->create();

    $question->delete();

    $this->assertSoftDeleted('questions', [
        'id' => $question->id,
    ]);
});

test('guests cannot access questions index', function () {
    $this->get(route('questions.index'))->assertRedirect(route('login'));
});

test('authenticated user can view their questions index page', function () {
    $user = User::factory()->create();
    Question::factory()->count(2)->for($user)->create();

    $this->actingAs($user)
        ->get(route('questions.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('questions/index')
            ->has('questions.data', 2)
        );
});

test('questions index only shows the users own questions', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    Question::factory()->count(3)->for($user)->create();
    Question::factory()->count(5)->for($other)->create();

    $this->actingAs($user)
        ->get(route('questions.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('questions.data', 3)
        );
});

test('questions index is paginated with ten per page', function () {
    $user = User::factory()->create();
    Question::factory()->count(12)->for($user)->create();

    $this->actingAs($user)
        ->get(route('questions.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('questions.per_page', 10)
            ->has('questions.data', 10)
        );
});

test('authenticated user can view edit page for their question', function () {
    $user = User::factory()->create();
    $question = Question::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('questions.edit', $question))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('questions/edit')
            ->where('question.id', $question->id)
            ->has('provinces')
            ->has('officialRoles')
        );
});

test('authenticated user cannot edit another users question', function () {
    $user = User::factory()->create();
    $question = Question::factory()->create();

    $this->actingAs($user)
        ->get(route('questions.edit', $question))
        ->assertForbidden();
});

test('authenticated user can complete draft and transition to pending', function () {
    $user = User::factory()->create();
    $question = Question::factory()->for($user)->create([
        'body' => 'Original',
        'status' => QuestionStatus::Incomplete,
        'official_role_id' => null,
        'effective_area' => null,
        'province_id' => null,
        'city_id' => null,
    ]);

    $this->actingAs($user)
        ->put(route('questions.update', $question), [
            'body' => 'Updated body text here.',
            'official_role_id' => $this->officialRole->id,
            'effective_area' => EffectiveArea::Public->value,
        ])
        ->assertRedirect(route('questions.index'));

    $question->refresh();
    expect($question->body)->toBe('Updated body text here.')
        ->and($question->status)->toBe(QuestionStatus::Pending);
});

test('authenticated user cannot update another users question', function () {
    $user = User::factory()->create();
    $question = Question::factory()->create([
        'body' => 'Original body',
    ]);

    $this->actingAs($user)
        ->put(route('questions.update', $question), [
            'body' => 'Hacked',
            'official_role_id' => $this->officialRole->id,
            'effective_area' => EffectiveArea::Public->value,
        ])
        ->assertForbidden();

    expect($question->fresh()->body)->toBe('Original body');
});

test('authenticated user can delete their question', function () {
    $user = User::factory()->create();
    $question = Question::factory()->for($user)->create();

    $this->actingAs($user)
        ->delete(route('questions.destroy', $question))
        ->assertRedirect(route('questions.index'));

    $this->assertSoftDeleted('questions', [
        'id' => $question->id,
    ]);
});

test('authenticated user cannot delete another users question', function () {
    $user = User::factory()->create();
    $question = Question::factory()->create();

    $this->actingAs($user)
        ->delete(route('questions.destroy', $question))
        ->assertForbidden();

    expect($question->fresh()->trashed())->toBeFalse();
});

test('update with effective_area public succeeds without province or city', function () {
    $user = User::factory()->create();
    $question = Question::factory()->for($user)->create([
        'body' => 'Text',
        'status' => QuestionStatus::Incomplete,
        'effective_area' => EffectiveArea::Province,
        'province_id' => Province::factory()->create()->id,
        'city_id' => null,
    ]);

    $this->actingAs($user)
        ->put(route('questions.update', $question), [
            'body' => 'Text',
            'official_role_id' => $this->officialRole->id,
            'effective_area' => EffectiveArea::Public->value,
        ])
        ->assertRedirect(route('questions.index'));

    $question->refresh();
    expect($question->effective_area)->toBe(EffectiveArea::Public)
        ->and($question->province_id)->toBeNull()
        ->and($question->city_id)->toBeNull()
        ->and($question->status)->toBe(QuestionStatus::Pending);
});

test('update with effective_area province requires province_id', function () {
    $user = User::factory()->create();
    $question = Question::factory()->for($user)->create([
        'status' => QuestionStatus::Incomplete,
    ]);

    $this->actingAs($user)
        ->put(route('questions.update', $question), [
            'body' => 'Text',
            'official_role_id' => $this->officialRole->id,
            'effective_area' => EffectiveArea::Province->value,
        ])
        ->assertSessionHasErrors('province_id');
});

test('update with effective_area city requires province_id and city_id', function () {
    $user = User::factory()->create();
    $question = Question::factory()->for($user)->create([
        'status' => QuestionStatus::Incomplete,
    ]);
    $province = Province::factory()->create();

    $this->actingAs($user)
        ->put(route('questions.update', $question), [
            'body' => 'Text',
            'official_role_id' => $this->officialRole->id,
            'effective_area' => EffectiveArea::City->value,
            'province_id' => $province->id,
        ])
        ->assertSessionHasErrors('city_id');
});

test('update with effective_area city rejects city not in selected province', function () {
    $user = User::factory()->create();
    $question = Question::factory()->for($user)->create([
        'status' => QuestionStatus::Incomplete,
    ]);
    $provinceA = Province::factory()->create();
    $provinceB = Province::factory()->create();
    $cityInB = City::factory()->create(['province' => $provinceB->id]);

    $this->actingAs($user)
        ->put(route('questions.update', $question), [
            'body' => 'Text',
            'official_role_id' => $this->officialRole->id,
            'effective_area' => EffectiveArea::City->value,
            'province_id' => $provinceA->id,
            'city_id' => $cityInB->id,
        ])
        ->assertSessionHasErrors('city_id');
});

test('update stores province_id and city_id correctly for city scope', function () {
    $user = User::factory()->create();
    $question = Question::factory()->for($user)->create([
        'status' => QuestionStatus::Incomplete,
    ]);
    $province = Province::factory()->create();
    $city = City::factory()->create(['province' => $province->id]);

    $this->actingAs($user)
        ->put(route('questions.update', $question), [
            'body' => 'Scoped',
            'official_role_id' => $this->officialRole->id,
            'effective_area' => EffectiveArea::City->value,
            'province_id' => $province->id,
            'city_id' => $city->id,
        ])
        ->assertRedirect(route('questions.index'));

    $question->refresh();
    expect($question->effective_area)->toBe(EffectiveArea::City)
        ->and($question->province_id)->toBe($province->id)
        ->and($question->city_id)->toBe($city->id)
        ->and($question->status)->toBe(QuestionStatus::Pending);
});

test('update with effective_area province stores province and clears city', function () {
    $user = User::factory()->create();
    $province = Province::factory()->create();
    $city = City::factory()->create(['province' => $province->id]);
    $question = Question::factory()->for($user)->create([
        'status' => QuestionStatus::Incomplete,
        'effective_area' => EffectiveArea::City,
        'province_id' => $province->id,
        'city_id' => $city->id,
    ]);

    $this->actingAs($user)
        ->put(route('questions.update', $question), [
            'body' => $question->body,
            'official_role_id' => $this->officialRole->id,
            'effective_area' => EffectiveArea::Province->value,
            'province_id' => $province->id,
        ])
        ->assertRedirect(route('questions.index'));

    $question->refresh();
    expect($question->effective_area)->toBe(EffectiveArea::Province)
        ->and($question->province_id)->toBe($province->id)
        ->and($question->city_id)->toBeNull()
        ->and($question->status)->toBe(QuestionStatus::Pending);
});

test('pending question does not update body from request', function () {
    $user = User::factory()->create();
    $question = Question::factory()->for($user)->create([
        'body' => 'Locked body',
        'status' => QuestionStatus::Pending,
        'effective_area' => EffectiveArea::Public,
        'official_role_id' => $this->officialRole->id,
    ]);

    $this->actingAs($user)
        ->put(route('questions.update', $question), [
            'body' => 'Attempted change',
            'official_role_id' => $this->officialRole->id,
            'effective_area' => EffectiveArea::Public->value,
        ])
        ->assertRedirect(route('questions.index'));

    expect($question->fresh()->body)->toBe('Locked body');
});

test('incomplete questions are not listed on public browse', function () {
    $owner = User::factory()->create();
    Question::factory()->for($owner)->create([
        'body' => 'Draft',
        'status' => QuestionStatus::Incomplete,
    ]);
    Question::factory()->create([
        'body' => 'Published',
        'status' => QuestionStatus::Pending,
    ]);

    $this->get(route('questions.browse'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('questions/browse')
            ->has('questions.data', 1)
            ->where('questions.data.0.body', 'Published')
        );
});

test('non-owner cannot view another users incomplete question', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $question = Question::factory()->for($owner)->create([
        'status' => QuestionStatus::Incomplete,
    ]);

    $this->actingAs($other)
        ->get(route('questions.show', $question))
        ->assertForbidden();
});

test('public show includes role and locale-capable area relations', function () {
    $user = User::factory()->create();
    $province = Province::factory()->create();
    $city = City::factory()->create(['province' => $province->id]);
    $question = Question::factory()->for($user)->create([
        'status' => QuestionStatus::Pending,
        'effective_area' => EffectiveArea::City,
        'official_role_id' => $this->officialRole->id,
        'province_id' => $province->id,
        'city_id' => $city->id,
    ]);

    $this->actingAs($user)
        ->get(route('questions.show', $question))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('questions/show')
            ->where('question.official_role.name', $this->officialRole->name)
            ->where('question.province.name', $province->name)
            ->where('question.city.name', $city->name)
        );
});

test('edit page only includes official roles with open windows', function () {
    $user = User::factory()->create();
    $question = Question::factory()->for($user)->create();

    $openPeriodicRole = OfficialRole::factory()->create([
        'name' => 'Open periodic role',
        'window_plan' => WindowPlan::Every2Months,
        'open_window_duration' => WindowDuration::SevenDays,
        'last_window_close_date' => now()->subMonthsNoOverflow(2)->subDays(3),
    ]);
    $closedPeriodicRole = OfficialRole::factory()->create([
        'name' => 'Closed periodic role',
        'window_plan' => WindowPlan::Every2Months,
        'open_window_duration' => WindowDuration::SevenDays,
        'last_window_close_date' => now()->subMonthsNoOverflow(2)->subDays(10),
    ]);
    $continuousRole = OfficialRole::factory()->create([
        'name' => 'Continuous role',
        'window_plan' => WindowPlan::Continuously,
        'open_window_duration' => WindowDuration::SevenDays,
    ]);

    $this->actingAs($user)
        ->get(route('questions.edit', $question))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('questions/edit')
            ->where('officialRoles', function ($roles) use ($openPeriodicRole, $closedPeriodicRole, $continuousRole): bool {
                $roleIds = collect($roles)->pluck('id');

                return $roleIds->contains($openPeriodicRole->id)
                    && $roleIds->contains($continuousRole->id)
                    && ! $roleIds->contains($closedPeriodicRole->id);
            })
        );
});

test('continuous role is always available on question edit page', function () {
    $user = User::factory()->create();
    $question = Question::factory()->for($user)->create();
    $continuousRole = OfficialRole::factory()->create([
        'name' => 'Always open role',
        'window_plan' => WindowPlan::Continuously,
        'open_window_duration' => WindowDuration::SevenDays,
    ]);

    $this->actingAs($user)
        ->get(route('questions.edit', $question))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('questions/edit')
            ->where('officialRoles', function ($roles) use ($continuousRole): bool {
                return collect($roles)->pluck('id')->contains($continuousRole->id);
            })
        );
});

test('update rejects official role when window is closed', function () {
    $user = User::factory()->create();
    $question = Question::factory()->for($user)->create([
        'status' => QuestionStatus::Incomplete,
    ]);
    $closedRole = OfficialRole::factory()->create([
        'window_plan' => WindowPlan::Every2Months,
        'open_window_duration' => WindowDuration::SevenDays,
        'last_window_close_date' => now()->subMonthsNoOverflow(2)->subDays(10),
    ]);

    $this->actingAs($user)
        ->put(route('questions.update', $question), [
            'body' => 'Text',
            'official_role_id' => $closedRole->id,
            'effective_area' => EffectiveArea::Public->value,
        ])
        ->assertSessionHasErrors('official_role_id');
});
