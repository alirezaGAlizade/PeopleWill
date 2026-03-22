<?php

namespace App\Policies;

use App\Enums\QuestionStatus;
use App\Models\Question;
use App\Models\User;

class QuestionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Question $question): bool
    {
        if ($question->status === QuestionStatus::Incomplete) {
            return $user->id === $question->user_id;
        }

        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Question $question): bool
    {
        return $user->id === $question->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Question $question): bool
    {
        return $user->id === $question->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Question $question): bool
    {
        return $user->id === $question->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Question $question): bool
    {
        return false;
    }

    /**
     * Official assigned to the question's role may post a primary or remediation response when allowed.
     */
    public function respondAsOfficial(User $user, Question $question): bool
    {
        if ($question->official_role_id === null) {
            return false;
        }

        if (! $user->officialRoles()->whereKey($question->official_role_id)->exists()) {
            return false;
        }

        if ($question->status === QuestionStatus::ForRoleUserAction) {
            return ! $question->questionResponses()->where('sequence', 1)->exists();
        }

        if ($question->status === QuestionStatus::ForRoleUserSecondAction) {
            if ($question->questionResponses()->where('sequence', 2)->exists()) {
                return false;
            }

            if ($question->second_response_deadline_at !== null && now()->greaterThan($question->second_response_deadline_at)) {
                return false;
            }

            return true;
        }

        return false;
    }
}
