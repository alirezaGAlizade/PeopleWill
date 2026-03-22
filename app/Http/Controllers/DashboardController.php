<?php

namespace App\Http\Controllers;

use App\Enums\QuestionStatus;
use App\Models\Question;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $roleIds = $user->officialRoles()->pluck('official_roles.id');

        $officialActionQuestions = Question::query()
            ->whereIn('official_role_id', $roleIds)
            ->whereIn('status', [
                QuestionStatus::ForRoleUserAction,
                QuestionStatus::ForRoleUserSecondAction,
            ])
            ->with(['officialRole:id,name'])
            ->latest()
            ->get();

        return Inertia::render('dashboard', [
            'officialActionQuestions' => $officialActionQuestions,
        ]);
    }
}
