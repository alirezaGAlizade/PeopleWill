<?php

use App\Enums\EffectiveArea;
use App\Models\City;
use App\Models\Province;
use App\Models\Question;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot store questions', function () {
    $this->post(route('questions.store'), [
        'body' => 'What is the meaning of life?',
    ])->assertRedirect(route('login'));
});

test('authenticated user can store a question', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('questions.store'), [
            'body' => 'What is the meaning of life?',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('questions', [
        'user_id' => $user->id,
        'body' => 'What is the meaning of life?',
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
        );
});

test('authenticated user cannot edit another users question', function () {
    $user = User::factory()->create();
    $question = Question::factory()->create();

    $this->actingAs($user)
        ->get(route('questions.edit', $question))
        ->assertForbidden();
});

test('authenticated user can update their question', function () {
    $user = User::factory()->create();
    $question = Question::factory()->for($user)->create([
        'body' => 'Original',
    ]);

    $this->actingAs($user)
        ->put(route('questions.update', $question), [
            'body' => 'Updated body text here.',
            'effective_area' => EffectiveArea::Public->value,
        ])
        ->assertRedirect(route('questions.index'));

    expect($question->fresh()->body)->toBe('Updated body text here.');
});

test('authenticated user cannot update another users question', function () {
    $user = User::factory()->create();
    $question = Question::factory()->create([
        'body' => 'Original body',
    ]);

    $this->actingAs($user)
        ->put(route('questions.update', $question), [
            'body' => 'Hacked',
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
        'effective_area' => EffectiveArea::Province,
        'province_id' => Province::factory()->create()->id,
        'city_id' => null,
    ]);

    $this->actingAs($user)
        ->put(route('questions.update', $question), [
            'body' => 'Text',
            'effective_area' => EffectiveArea::Public->value,
        ])
        ->assertRedirect(route('questions.index'));

    $question->refresh();
    expect($question->effective_area)->toBe(EffectiveArea::Public)
        ->and($question->province_id)->toBeNull()
        ->and($question->city_id)->toBeNull();
});

test('update with effective_area province requires province_id', function () {
    $user = User::factory()->create();
    $question = Question::factory()->for($user)->create();

    $this->actingAs($user)
        ->put(route('questions.update', $question), [
            'body' => 'Text',
            'effective_area' => EffectiveArea::Province->value,
        ])
        ->assertSessionHasErrors('province_id');
});

test('update with effective_area city requires province_id and city_id', function () {
    $user = User::factory()->create();
    $question = Question::factory()->for($user)->create();
    $province = Province::factory()->create();

    $this->actingAs($user)
        ->put(route('questions.update', $question), [
            'body' => 'Text',
            'effective_area' => EffectiveArea::City->value,
            'province_id' => $province->id,
        ])
        ->assertSessionHasErrors('city_id');
});

test('update with effective_area city rejects city not in selected province', function () {
    $user = User::factory()->create();
    $question = Question::factory()->for($user)->create();
    $provinceA = Province::factory()->create();
    $provinceB = Province::factory()->create();
    $cityInB = City::factory()->create(['province' => $provinceB->id]);

    $this->actingAs($user)
        ->put(route('questions.update', $question), [
            'body' => 'Text',
            'effective_area' => EffectiveArea::City->value,
            'province_id' => $provinceA->id,
            'city_id' => $cityInB->id,
        ])
        ->assertSessionHasErrors('city_id');
});

test('update stores province_id and city_id correctly for city scope', function () {
    $user = User::factory()->create();
    $question = Question::factory()->for($user)->create();
    $province = Province::factory()->create();
    $city = City::factory()->create(['province' => $province->id]);

    $this->actingAs($user)
        ->put(route('questions.update', $question), [
            'body' => 'Scoped',
            'effective_area' => EffectiveArea::City->value,
            'province_id' => $province->id,
            'city_id' => $city->id,
        ])
        ->assertRedirect(route('questions.index'));

    $question->refresh();
    expect($question->effective_area)->toBe(EffectiveArea::City)
        ->and($question->province_id)->toBe($province->id)
        ->and($question->city_id)->toBe($city->id);
});

test('update with effective_area province stores province and clears city', function () {
    $user = User::factory()->create();
    $province = Province::factory()->create();
    $city = City::factory()->create(['province' => $province->id]);
    $question = Question::factory()->for($user)->create([
        'effective_area' => EffectiveArea::City,
        'province_id' => $province->id,
        'city_id' => $city->id,
    ]);

    $this->actingAs($user)
        ->put(route('questions.update', $question), [
            'body' => $question->body,
            'effective_area' => EffectiveArea::Province->value,
            'province_id' => $province->id,
        ])
        ->assertRedirect(route('questions.index'));

    $question->refresh();
    expect($question->effective_area)->toBe(EffectiveArea::Province)
        ->and($question->province_id)->toBe($province->id)
        ->and($question->city_id)->toBeNull();
});
