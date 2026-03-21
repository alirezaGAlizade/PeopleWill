<?php

use App\Models\Question;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests can access public questions browse page', function () {
    Question::factory()->count(3)->create();

    $this->get(route('questions.browse'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('questions/browse')
            ->has('questions.data', 3)
        );
});

test('public questions browse page paginates with twenty items', function () {
    Question::factory()->count(25)->create();

    $this->get(route('questions.browse'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('questions.per_page', 20)
            ->has('questions.data', 20)
        );
});

test('guests cannot access public question show page', function () {
    $question = Question::factory()->create();

    $this->get(route('questions.show', $question))
        ->assertRedirect(route('login'));
});

test('authenticated users can view public question show page', function () {
    $user = User::factory()->create();
    $question = Question::factory()->create();

    $this->actingAs($user)
        ->get(route('questions.show', $question))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('questions/show')
            ->where('question.id', $question->id)
        );
});

test('question visits increment only once per unique user', function () {
    $viewer = User::factory()->create();
    $question = Question::factory()->create(['visits' => 0]);

    $this->actingAs($viewer)->get(route('questions.show', $question))->assertOk();
    $this->actingAs($viewer)->get(route('questions.show', $question))->assertOk();

    expect($question->fresh()->visits)->toBe(1);

    $this->assertDatabaseCount('question_visits', 1);
    $this->assertDatabaseHas('question_visits', [
        'question_id' => $question->id,
        'user_id' => $viewer->id,
    ]);
});
