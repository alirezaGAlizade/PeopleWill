<?php

use App\Enums\EffectiveArea;
use App\Enums\MandatoryResponseThresholdPercent;
use App\Enums\QuestionStatus;
use App\Enums\VoteType;
use App\Models\OfficialRole;
use App\Models\Question;
use App\Models\User;
use Illuminate\Support\Carbon;

test('first upvote in electorate triggers mandatory response state', function () {
    $voter = User::factory()->create();
    $role = OfficialRole::factory()->create([
        'country_id' => $voter->country_id,
        'mandatory_response_threshold' => MandatoryResponseThresholdPercent::Percent5,
        'response_deadline_days' => 7,
    ]);
    $question = Question::factory()->create([
        'official_role_id' => $role->id,
        'effective_area' => EffectiveArea::Public,
        'status' => QuestionStatus::Pending,
    ]);

    $this->actingAs($voter)
        ->post(route('votes.toggle', [
            'voteable_type' => 'question',
            'voteable_id' => $question->id,
        ]), [
            'type' => VoteType::Up->value,
        ])
        ->assertRedirect();

    $question->refresh();

    expect($question->status)->toBe(QuestionStatus::ForRoleUserAction)
        ->and($question->response_deadline_at)->not->toBeNull();
});

test('response deadline command marks question not accepted without primary response', function () {
    $voter = User::factory()->create();
    $role = OfficialRole::factory()->create([
        'country_id' => $voter->country_id,
        'mandatory_response_threshold' => MandatoryResponseThresholdPercent::Percent5,
        'response_deadline_days' => 7,
    ]);
    $question = Question::factory()->create([
        'official_role_id' => $role->id,
        'effective_area' => EffectiveArea::Public,
        'status' => QuestionStatus::ForRoleUserAction,
        'response_deadline_at' => Carbon::parse('2020-01-01 12:00:00'),
    ]);

    $this->artisan('questions:evaluate-response-deadlines')->assertSuccessful();

    expect($question->fresh()->status)->toBe(QuestionStatus::RoleUserActionsNotAccepted);
});

test('validation window command marks satisfied question when majority of electorate accepts', function () {
    Carbon::setTestNow('2025-06-01 12:00:00');

    $official = User::factory()->create();
    $voter = User::factory()->create([
        'country_id' => $official->country_id,
        'province_id' => $official->province_id,
        'city_id' => $official->city_id,
    ]);

    $role = OfficialRole::factory()->create([
        'country_id' => $voter->country_id,
        'participation_quorum_percent' => 1,
        'response_rejection_downvote_percent' => 50,
    ]);

    $question = Question::factory()->create([
        'official_role_id' => $role->id,
        'effective_area' => EffectiveArea::Public,
        'status' => QuestionStatus::NeedPeopleValidateResponse,
        'response_validation_ends_at' => Carbon::parse('2025-06-02 12:00:00'),
    ]);

    $primary = $question->questionResponses()->create([
        'user_id' => $official->id,
        'body' => 'Official answer',
        'sequence' => 1,
    ]);

    $primary->votes()->create([
        'user_id' => $voter->id,
        'type' => VoteType::Up->value,
    ]);

    Carbon::setTestNow('2025-06-03 12:00:00');

    $this->artisan('questions:evaluate-validation-windows')->assertSuccessful();

    expect($question->fresh()->status)->toBe(QuestionStatus::Done);

    Carbon::setTestNow();
});

test('users outside the electorate cannot vote on a question', function () {
    $voter = User::factory()->create();
    $other = User::factory()->create();

    $role = OfficialRole::factory()->create(['country_id' => $other->country_id]);
    $question = Question::factory()->create([
        'official_role_id' => $role->id,
        'effective_area' => EffectiveArea::Public,
    ]);

    $this->actingAs($voter)
        ->post(route('votes.toggle', [
            'voteable_type' => 'question',
            'voteable_id' => $question->id,
        ]), [
            'type' => VoteType::Up->value,
        ])
        ->assertForbidden();
});
