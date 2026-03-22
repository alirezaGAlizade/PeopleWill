<?php

namespace App\Http\Controllers;

use App\Enums\VoteType;
use App\Http\Requests\ToggleVoteRequest;
use App\Models\Question;
use App\Models\QuestionResponse;
use App\Models\User;
use App\Services\ElectorateScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class VoteController extends Controller
{
    public function __construct(
        private ElectorateScope $electorateScope,
    ) {}

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

        $this->authorizeVoteForElectorate($request->user(), $voteable);

        $voteable->toggleVote($request->user(), $voteType);

        return redirect()->back();
    }

    private function authorizeVoteForElectorate(User $user, Model $voteable): void
    {
        if ($voteable instanceof Question) {
            if (! $this->electorateScope->userMatchesQuestionElectorate($user, $voteable)) {
                abort(403);
            }

            return;
        }

        if ($voteable instanceof QuestionResponse) {
            $voteable->loadMissing('question');
            $question = $voteable->question;

            if ($question === null || ! $this->electorateScope->userMatchesQuestionElectorate($user, $question)) {
                abort(403);
            }

            return;
        }
    }

    private function resolveVoteable(string $voteableType, int $voteableId): Model
    {
        $voteableClass = match ($voteableType) {
            'question' => Question::class,
            'question_response' => QuestionResponse::class,
            default => null,
        };

        if (! is_string($voteableClass)) {
            abort(404);
        }

        return $voteableClass::query()->findOrFail($voteableId);
    }
}
