<?php

namespace App\Http\Controllers;

use App\Enums\QuestionStatus;
use App\Models\Question;
use App\Models\QuestionVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PublicQuestionController extends Controller
{
    public function browse(): Response
    {
        return Inertia::render('questions/browse', [
            'questions' => Question::query()
                ->where('status', '!=', QuestionStatus::Incomplete)
                ->with(['user:id,name'])
                ->withCount('upvotes')
                ->latest()
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function show(Request $request, Question $question): Response
    {
        if ($question->status === QuestionStatus::Incomplete && $question->user_id !== (int) $request->user()->id) {
            abort(403);
        }

        $this->authorize('view', $question);
        $this->recordUniqueVisit($question, (int) $request->user()->id);

        $question->load([
            'user:id,name',
            'officialRole:id,name',
            'province:id,name,name_en',
            'city:id,name,name_en,province',
        ])->loadCount('upvotes');

        return Inertia::render('questions/show', [
            'question' => $question,
            'userVote' => $question->voteTypeForUser($request->user()),
        ]);
    }

    private function recordUniqueVisit(Question $question, int $userId): void
    {
        DB::transaction(function () use ($question, $userId): void {
            $visit = QuestionVisit::query()->firstOrCreate([
                'question_id' => $question->id,
                'user_id' => $userId,
            ]);

            if ($visit->wasRecentlyCreated) {
                $question->increment('visits');
            }
        });
    }
}
