<?php

namespace App\Http\Controllers;

use App\Enums\QuestionStatus;
use App\Http\Requests\StoreOfficialQuestionResponseRequest;
use App\Models\Question;
use App\Services\QuestionLifecycleService;
use Illuminate\Http\RedirectResponse;

class OfficialQuestionResponseController extends Controller
{
    public function store(
        StoreOfficialQuestionResponseRequest $request,
        Question $question,
        QuestionLifecycleService $lifecycle,
    ): RedirectResponse {
        $body = $request->validated('body');

        if ($question->status === QuestionStatus::ForRoleUserAction) {
            $lifecycle->recordPrimaryResponse($question, $request->user(), $body);
        } elseif ($question->status === QuestionStatus::ForRoleUserSecondAction) {
            $lifecycle->recordSecondResponse($question, $request->user(), $body);
        } else {
            abort(403);
        }

        return redirect()->back();
    }
}
