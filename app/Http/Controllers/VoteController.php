<?php

namespace App\Http\Controllers;

use App\Enums\VoteType;
use App\Http\Requests\ToggleVoteRequest;
use App\Models\Question;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class VoteController extends Controller
{
    public function toggle(
        ToggleVoteRequest $request,
        string $voteableType,
        int $voteableId,
    ): RedirectResponse {
        $voteable = $this->resolveVoteable($voteableType, $voteableId);
        $voteType = VoteType::from($request->validated('type'));

        if (! method_exists($voteable, 'supportsVoteType') || ! $voteable->supportsVoteType($voteType)) {
            throw new HttpException(422, 'Vote type is not supported for this resource.');
        }

        $voteable->toggleVote($request->user(), $voteType);

        return redirect()->back();
    }

    private function resolveVoteable(string $voteableType, int $voteableId): Model
    {
        $voteableClass = match ($voteableType) {
            'question' => Question::class,
            default => null,
        };

        if (! is_string($voteableClass)) {
            abort(404);
        }

        return $voteableClass::query()->findOrFail($voteableId);
    }
}
