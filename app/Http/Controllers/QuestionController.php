<?php

namespace App\Http\Controllers;

use App\Enums\EffectiveArea;
use App\Enums\QuestionStatus;
use App\Http\Requests\StoreQuestionRequest;
use App\Http\Requests\UpdateQuestionRequest;
use App\Models\OfficialRole;
use App\Models\Province;
use App\Models\Question;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuestionController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Question::class);

        $questions = $request->user()
            ->questions()
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('questions/index', [
            'questions' => $questions,
        ]);
    }

    public function edit(Request $request, Question $question): Response
    {
        $this->authorize('update', $question);

        return Inertia::render('questions/edit', [
            'question' => $question->load(['province', 'city']),
            'provinces' => Province::query()
                ->select('id', 'name', 'name_en')
                ->orderBy('name')
                ->get(),
            'officialRoles' => OfficialRole::query()
                ->withOpenWindow()
                ->select('id', 'name', 'slug')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(UpdateQuestionRequest $request, Question $question): RedirectResponse
    {
        $validated = $request->validated();
        $effectiveArea = EffectiveArea::from($validated['effective_area']);

        $payload = [
            'official_role_id' => (int) $validated['official_role_id'],
            'effective_area' => $effectiveArea,
        ];

        if (array_key_exists('body', $validated)) {
            $payload['body'] = $validated['body'];
        }

        $payload = match ($effectiveArea) {
            EffectiveArea::Public => [...$payload, 'province_id' => null, 'city_id' => null],
            EffectiveArea::Province => [...$payload, 'province_id' => $validated['province_id'], 'city_id' => null],
            EffectiveArea::City => [...$payload, 'province_id' => $validated['province_id'], 'city_id' => $validated['city_id']],
        };

        $isCompleting = $question->status === QuestionStatus::Incomplete;

        if ($isCompleting) {
            $payload['status'] = QuestionStatus::Pending;
        }

        if (! $isCompleting) {
            unset($payload['body']);
        }

        $question->update($payload);

        return redirect()->route('questions.index');
    }

    public function destroy(Question $question): RedirectResponse
    {
        $this->authorize('delete', $question);

        $question->delete();

        return redirect()->route('questions.index');
    }

    public function store(StoreQuestionRequest $request): RedirectResponse
    {
        $question = $request->user()->questions()->create([
            'body' => $request->validated('body'),
            'official_role_id' => null,
            'status' => QuestionStatus::Incomplete,
        ]);

        return redirect()->route('questions.edit', $question);
    }
}
