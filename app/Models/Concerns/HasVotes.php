<?php

namespace App\Models\Concerns;

use App\Enums\VoteType;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasVotes
{
    /**
     * @return MorphMany<Vote, $this>
     */
    public function votes(): MorphMany
    {
        return $this->morphMany(Vote::class, 'voteable');
    }

    /**
     * @return MorphMany<Vote, $this>
     */
    public function upvotes(): MorphMany
    {
        return $this->votes()->where('type', VoteType::Up->value);
    }

    /**
     * @return MorphMany<Vote, $this>
     */
    public function downvotes(): MorphMany
    {
        return $this->votes()->where('type', VoteType::Down->value);
    }

    public function allowsDownvotes(): bool
    {
        if (! property_exists($this, 'allowDownvotes')) {
            return true;
        }

        return (bool) $this->allowDownvotes;
    }

    public function supportsVoteType(VoteType $type): bool
    {
        return $type === VoteType::Up || $this->allowsDownvotes();
    }

    public function voteTypeForUser(User $user): ?VoteType
    {
        /** @var Vote|null $vote */
        $vote = $this->votes()->where('user_id', $user->id)->first();

        if (! $vote instanceof Vote) {
            return null;
        }

        return $vote->type;
    }

    public function toggleVote(User $user, VoteType $type): ?Vote
    {
        /** @var Vote|null $vote */
        $vote = $this->votes()->where('user_id', $user->id)->first();

        if ($vote instanceof Vote && $vote->type === $type) {
            $vote->delete();

            return null;
        }

        if ($vote instanceof Vote) {
            $vote->update(['type' => $type]);

            return $vote->refresh();
        }

        /** @var Vote $createdVote */
        $createdVote = $this->votes()->create([
            'user_id' => $user->id,
            'type' => $type,
        ]);

        return $createdVote;
    }
}
