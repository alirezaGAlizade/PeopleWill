<?php

namespace App\Observers;

use App\Models\Question;
use App\Models\Vote;
use App\Services\QuestionLifecycleService;

class VoteObserver
{
    public function saved(Vote $vote): void
    {
        $vote->loadMissing('voteable');
        $voteable = $vote->voteable;

        if ($voteable instanceof Question) {
            app(QuestionLifecycleService::class)->maybeEscalateToMandatoryResponse($voteable);
        }
    }
}
