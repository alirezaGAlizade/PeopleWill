<?php

use App\Enums\VoteType;
use App\Models\Question;
use App\Models\User;

test('authenticated user can upvote a question', function () {
    $user = User::factory()->create();
    $question = Question::factory()->create();

    $this->actingAs($user)
        ->post(route('votes.toggle', [
            'voteable_type' => 'question',
            'voteable_id' => $question->id,
        ]), [
            'type' => VoteType::Up->value,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('votes', [
        'user_id' => $user->id,
        'voteable_type' => Question::class,
        'voteable_id' => $question->id,
        'type' => VoteType::Up->value,
    ]);
});

test('upvoting same question twice toggles the vote off', function () {
    $user = User::factory()->create();
    $question = Question::factory()->create();

    $payload = [
        'type' => VoteType::Up->value,
    ];
    $routeParameters = [
        'voteable_type' => 'question',
        'voteable_id' => $question->id,
    ];

    $this->actingAs($user)->post(route('votes.toggle', $routeParameters), $payload);
    $this->actingAs($user)->post(route('votes.toggle', $routeParameters), $payload);

    $this->assertDatabaseMissing('votes', [
        'user_id' => $user->id,
        'voteable_type' => Question::class,
        'voteable_id' => $question->id,
    ]);
});

test('guests cannot vote', function () {
    $question = Question::factory()->create();

    $this->post(route('votes.toggle', [
        'voteable_type' => 'question',
        'voteable_id' => $question->id,
    ]), [
        'type' => VoteType::Up->value,
    ])->assertRedirect(route('login'));
});

test('downvote is rejected for question', function () {
    $user = User::factory()->create();
    $question = Question::factory()->create();

    $this->actingAs($user)
        ->post(route('votes.toggle', [
            'voteable_type' => 'question',
            'voteable_id' => $question->id,
        ]), [
            'type' => VoteType::Down->value,
        ])
        ->assertUnprocessable();
});

test('question upvotes count reflects persisted votes', function () {
    $question = Question::factory()->create();
    $firstVoter = User::factory()->create();
    $secondVoter = User::factory()->create();

    $firstVoter->votes()->create([
        'voteable_type' => Question::class,
        'voteable_id' => $question->id,
        'type' => VoteType::Up->value,
    ]);
    $secondVoter->votes()->create([
        'voteable_type' => Question::class,
        'voteable_id' => $question->id,
        'type' => VoteType::Up->value,
    ]);

    expect($question->fresh()->upvotes()->count())->toBe(2);
});
