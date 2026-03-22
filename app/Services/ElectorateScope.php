<?php

namespace App\Services;

use App\Enums\EffectiveArea;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ElectorateScope
{
    /**
     * Registered users eligible to participate for this question's jurisdiction.
     */
    public function electoratePopulation(Question $question): int
    {
        $query = $this->electorateQuery($question);

        return $query->count();
    }

    /**
     * @return Builder<User>
     */
    public function electorateQuery(Question $question): Builder
    {
        $question->loadMissing('officialRole');

        $role = $question->officialRole;

        if ($role === null || $question->effective_area === null) {
            return User::query()->whereRaw('1 = 0');
        }

        return match ($question->effective_area) {
            EffectiveArea::Public => User::query()->when(
                $role->country_id !== null,
                fn (Builder $q) => $q->where('country_id', $role->country_id),
                fn (Builder $q) => $q->whereRaw('1 = 0'),
            ),
            EffectiveArea::Province => User::query()->when(
                $question->province_id !== null,
                fn (Builder $q) => $q->where('province_id', $question->province_id),
                fn (Builder $q) => $q->whereRaw('1 = 0'),
            ),
            EffectiveArea::City => User::query()->when(
                $question->city_id !== null,
                fn (Builder $q) => $q->where('city_id', $question->city_id),
                fn (Builder $q) => $q->whereRaw('1 = 0'),
            ),
        };
    }

    public function userMatchesQuestionElectorate(User $user, Question $question): bool
    {
        $question->loadMissing('officialRole');

        $role = $question->officialRole;

        if ($role === null || $question->effective_area === null) {
            return false;
        }

        return match ($question->effective_area) {
            EffectiveArea::Public => $role->country_id !== null
                && $user->country_id !== null
                && (int) $user->country_id === (int) $role->country_id,
            EffectiveArea::Province => $question->province_id !== null
                && $user->province_id !== null
                && (int) $user->province_id === (int) $question->province_id,
            EffectiveArea::City => $question->city_id !== null
                && $user->city_id !== null
                && (int) $user->city_id === (int) $question->city_id,
        };
    }

    /**
     * Minimum upvotes on question support to trigger mandatory response.
     */
    public function mandatorySupportThresholdCount(Question $question, int $thresholdPercent): int
    {
        $population = $this->electoratePopulation($question);

        if ($population === 0) {
            return PHP_INT_MAX;
        }

        return (int) ceil($population * ($thresholdPercent / 100));
    }

    /**
     * @return array{satisfied: bool, upvote_ratio: float|null}
     */
    public function satisfactionFromUpvoteRatio(int $upvotes, int $electoratePopulation): array
    {
        if ($electoratePopulation === 0) {
            return ['satisfied' => false, 'upvote_ratio' => null];
        }

        $ratio = $upvotes / $electoratePopulation;

        return [
            'satisfied' => $ratio > 0.5,
            'upvote_ratio' => $ratio,
        ];
    }

    /**
     * Turnout: distinct voters who cast any vote on the response / electorate.
     *
     * @return array{meets_quorum: bool, turnout_ratio: float|null, voter_count: int}
     */
    public function responseVoteTurnout(int $distinctVoters, int $electoratePopulation, int $quorumPercent): array
    {
        if ($electoratePopulation === 0) {
            return ['meets_quorum' => false, 'turnout_ratio' => null, 'voter_count' => $distinctVoters];
        }

        $ratio = $distinctVoters / $electoratePopulation;

        return [
            'meets_quorum' => $ratio >= ($quorumPercent / 100),
            'turnout_ratio' => $ratio,
            'voter_count' => $distinctVoters,
        ];
    }

    /**
     * Downvotes as share of electorate (for remediation when quorum met).
     */
    public function downvoteRejectionMet(int $downvotes, int $electoratePopulation, int $rejectionPercent): bool
    {
        if ($electoratePopulation === 0) {
            return false;
        }

        return ($downvotes / $electoratePopulation) >= ($rejectionPercent / 100);
    }
}
