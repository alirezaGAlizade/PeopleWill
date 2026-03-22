<?php

namespace App\Services;

use App\Enums\QuestionStatus;
use App\Models\Question;
use App\Models\QuestionResponse;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class QuestionLifecycleService
{
    public const int VALIDATION_WINDOW_DAYS = 30;

    public const int SECOND_RESPONSE_WINDOW_DAYS = 7;

    public const int REMEDIATION_REVIEW_DAYS = 7;

    public function __construct(
        private ElectorateScope $electorateScope,
    ) {}

    public function maybeEscalateToMandatoryResponse(Question $question): void
    {
        $question->refresh();

        if ($question->status !== QuestionStatus::Pending) {
            return;
        }

        $role = $question->officialRole;

        if ($role === null) {
            return;
        }

        $population = $this->electorateScope->electoratePopulation($question);

        if ($population === 0) {
            return;
        }

        $thresholdPercent = $role->mandatory_response_threshold->value;
        $required = (int) ceil($population * ($thresholdPercent / 100));
        $upvotes = $question->upvotes()->count();

        if ($upvotes < $required) {
            return;
        }

        $question->forceFill([
            'status' => QuestionStatus::ForRoleUserAction,
            'threshold_met_at' => now(),
            'response_deadline_at' => now()->addDays($role->response_deadline_days),
        ])->save();
    }

    public function recordPrimaryResponse(Question $question, User $author, string $body): QuestionResponse
    {
        return DB::transaction(function () use ($question, $author, $body): QuestionResponse {
            $question->refresh();

            if ($question->status !== QuestionStatus::ForRoleUserAction) {
                abort(403);
            }

            if ($question->questionResponses()->where('sequence', 1)->exists()) {
                abort(403);
            }

            $response = $question->questionResponses()->create([
                'user_id' => $author->id,
                'body' => $body,
                'sequence' => 1,
            ]);

            $question->forceFill([
                'status' => QuestionStatus::NeedPeopleValidateResponse,
                'response_deadline_at' => null,
                'response_validation_ends_at' => now()->addDays(self::VALIDATION_WINDOW_DAYS),
            ])->save();

            return $response;
        });
    }

    public function recordSecondResponse(Question $question, User $author, string $body): QuestionResponse
    {
        return DB::transaction(function () use ($question, $author, $body): QuestionResponse {
            $question->refresh();

            if ($question->status !== QuestionStatus::ForRoleUserSecondAction) {
                abort(403);
            }

            if ($question->questionResponses()->where('sequence', 2)->exists()) {
                abort(403);
            }

            if ($question->second_response_deadline_at !== null && now()->greaterThan($question->second_response_deadline_at)) {
                abort(403);
            }

            $response = $question->questionResponses()->create([
                'user_id' => $author->id,
                'body' => $body,
                'sequence' => 2,
            ]);

            $question->forceFill([
                'second_response_deadline_at' => null,
                'second_response_posted_at' => now(),
                'remediation_review_ends_at' => now()->addDays(self::REMEDIATION_REVIEW_DAYS),
            ])->save();

            return $response;
        });
    }

    public function processExpiredResponseDeadlines(): int
    {
        $count = 0;

        Question::query()
            ->where('status', QuestionStatus::ForRoleUserAction)
            ->whereNotNull('response_deadline_at')
            ->where('response_deadline_at', '<', now())
            ->each(function (Question $question) use (&$count): void {
                if ($question->questionResponses()->where('sequence', 1)->exists()) {
                    return;
                }

                $question->forceFill([
                    'status' => QuestionStatus::RoleUserActionsNotAccepted,
                    'response_deadline_at' => null,
                ])->save();

                $count++;
            });

        return $count;
    }

    public function processExpiredValidationWindows(): int
    {
        $count = 0;

        Question::query()
            ->where('status', QuestionStatus::NeedPeopleValidateResponse)
            ->whereNotNull('response_validation_ends_at')
            ->where('response_validation_ends_at', '<=', now())
            ->each(function (Question $question) use (&$count): void {
                $this->finalizeValidationWindow($question);
                $count++;
            });

        return $count;
    }

    public function finalizeValidationWindow(Question $question): void
    {
        $role = $question->officialRole;

        if ($role === null) {
            return;
        }

        $primary = $question->questionResponses()->where('sequence', 1)->first();

        if ($primary === null) {
            $question->forceFill([
                'status' => QuestionStatus::Done,
                'response_validation_ends_at' => null,
            ])->save();

            return;
        }

        $population = $this->electorateScope->electoratePopulation($question);

        if ($population === 0) {
            $question->forceFill([
                'status' => QuestionStatus::Done,
                'response_validation_ends_at' => null,
            ])->save();

            return;
        }

        $upvotes = $primary->upvotes()->count();
        $satisfaction = $this->electorateScope->satisfactionFromUpvoteRatio($upvotes, $population);

        if ($satisfaction['satisfied']) {
            $question->forceFill([
                'status' => QuestionStatus::Done,
                'response_validation_ends_at' => null,
            ])->save();

            return;
        }

        $voteCount = $primary->votes()->count();
        $turnout = $this->electorateScope->responseVoteTurnout(
            $voteCount,
            $population,
            $role->participation_quorum_percent,
        );

        $downvotes = $primary->downvotes()->count();
        $rejection = $this->electorateScope->downvoteRejectionMet(
            $downvotes,
            $population,
            $role->response_rejection_downvote_percent,
        );

        if ($turnout['meets_quorum'] && $rejection) {
            $question->forceFill([
                'status' => QuestionStatus::ForRoleUserSecondAction,
                'response_validation_ends_at' => null,
                'second_response_deadline_at' => now()->addDays(self::SECOND_RESPONSE_WINDOW_DAYS),
            ])->save();

            return;
        }

        $question->forceFill([
            'status' => QuestionStatus::Done,
            'response_validation_ends_at' => null,
        ])->save();
    }

    public function processExpiredRemediationWindows(): int
    {
        $count = 0;

        Question::query()
            ->where('status', QuestionStatus::ForRoleUserSecondAction)
            ->where(function ($q): void {
                $q->where(function ($q2): void {
                    $q2->whereNull('second_response_posted_at')
                        ->whereNotNull('second_response_deadline_at')
                        ->where('second_response_deadline_at', '<', now());
                })->orWhere(function ($q2): void {
                    $q2->whereNotNull('second_response_posted_at')
                        ->whereNotNull('remediation_review_ends_at')
                        ->where('remediation_review_ends_at', '<=', now());
                });
            })
            ->each(function (Question $question) use (&$count): void {
                if ($question->second_response_posted_at === null) {
                    if ($question->questionResponses()->where('sequence', 2)->exists()) {
                        return;
                    }

                    $question->forceFill([
                        'status' => QuestionStatus::RoleUserActionsNotAccepted,
                        'second_response_deadline_at' => null,
                    ])->save();
                    $count++;

                    return;
                }

                $this->finalizeRemediationReview($question);
                $count++;
            });

        return $count;
    }

    public function finalizeRemediationReview(Question $question): void
    {
        $role = $question->officialRole;

        if ($role === null) {
            return;
        }

        $primary = $question->questionResponses()->where('sequence', 1)->first();

        if ($primary === null) {
            $question->forceFill([
                'status' => QuestionStatus::Done,
                'remediation_review_ends_at' => null,
            ])->save();

            return;
        }

        $population = $this->electorateScope->electoratePopulation($question);

        if ($population === 0) {
            $question->forceFill([
                'status' => QuestionStatus::Done,
                'remediation_review_ends_at' => null,
            ])->save();

            return;
        }

        $voteCount = $primary->votes()->count();
        $turnout = $this->electorateScope->responseVoteTurnout(
            $voteCount,
            $population,
            $role->participation_quorum_percent,
        );

        $downvotes = $primary->downvotes()->count();
        $rejection = $this->electorateScope->downvoteRejectionMet(
            $downvotes,
            $population,
            $role->response_rejection_downvote_percent,
        );

        if ($turnout['meets_quorum'] && $rejection) {
            $question->forceFill([
                'status' => QuestionStatus::RoleUserActionsNotAccepted,
                'remediation_review_ends_at' => null,
            ])->save();

            return;
        }

        $question->forceFill([
            'status' => QuestionStatus::Done,
            'remediation_review_ends_at' => null,
        ])->save();
    }
}
