---
name: question-management
description: Civic question submission and lifecycle management in NPAP. Activate when creating, editing, listing, deleting, or extending questions; working with Question status workflow (including mandatory response, validation, remediation); QuestionController, PublicQuestionController, QuestionPolicy, StoreQuestionRequest, UpdateQuestionRequest, QuestionFactory, welcome submission, My Questions, window-aware role filtering, electorate-scoped voting, QuestionLifecycleService, VoteObserver, official responses, dashboard official-action list, or no-open-window UX.
---

# Question Management

## Architecture

Questions are the entry point of the civic accountability workflow in the Civic Cases bounded context. After a question is **pending**, support thresholds, official responses, public validation voting, and scheduled jobs drive transitions documented in part here and in detail in `.cursor/skills/question-responses/SKILL.md`.

## Key Files

| Layer | File | Purpose |
|-------|------|---------|
| Enum | `app/Enums/QuestionStatus.php` | Full status lifecycle including validation and remediation |
| Model | `app/Models/Question.php` | Fillable fields, enum casts, relations, `isComplete()` |
| Model | `app/Models/QuestionResponse.php` | Official answers (sequence 1 primary, 2 follow-up); see question-responses skill |
| Policy | `app/Policies/QuestionPolicy.php` | Incomplete visibility; update/delete ownership; `respondAsOfficial()` for posting responses |
| Controller | `app/Http/Controllers/QuestionController.php` | Authenticated create/edit/update/delete for user-owned questions |
| Public controller | `app/Http/Controllers/PublicQuestionController.php` | Browse/show; show loads `questionResponses`, `canRespondAsOfficial`, `userVote` |
| Controller | `app/Http/Controllers/DashboardController.php` | Dashboard lists questions awaiting official action (`ForRoleUserAction`, `ForRoleUserSecondAction`) |
| Controller | `app/Http/Controllers/OfficialQuestionResponseController.php` | `POST` official primary or second response |
| Service | `app/Services/ElectorateScope.php` | Electorate population and `userMatchesQuestionElectorate()` for jurisdiction |
| Service | `app/Services/QuestionLifecycleService.php` | Threshold escalation, deadlines, validation/remediation windows |
| Observer | `app/Observers/VoteObserver.php` | On `Vote` saved for a `Question`, may escalate `Pending` → `ForRoleUserAction` |
| Validation | `app/Http/Requests/StoreQuestionRequest.php` | Body-only creation validation |
| Validation | `app/Http/Requests/UpdateQuestionRequest.php` | Status-aware update validation and area rules |
| Validation | `app/Http/Requests/StoreOfficialQuestionResponseRequest.php` | Official response body |
| Enum dependency | `app/Enums/WindowPlan.php` | Official role window cadence used by role availability logic |
| Enum dependency | `app/Enums/WindowDuration.php` | Official role duration used by role availability logic |
| Migration | `database/migrations/2026_03_20_181542_create_questions_table.php` | Base questions schema |
| Migration | `database/migrations/2026_03_22_002608_add_official_role_id_to_questions_table.php` | Nullable `official_role_id` FK |
| Migration | `database/migrations/2026_03_22_005438_add_status_to_questions_table.php` | `status` column defaulting to `incomplete` |
| Migration | `database/migrations/2026_03_22_152256_add_accountability_fields_to_questions_table.php` | Response/validation/remediation timestamps |
| Factory | `database/factories/QuestionFactory.php` | Defaults include `status = pending` for visible test records |
| Console | `app/Console/Commands/EvaluateQuestionResponseDeadlinesCommand.php` | `questions:evaluate-response-deadlines` |
| Console | `app/Console/Commands/EvaluateQuestionValidationWindowsCommand.php` | `questions:evaluate-validation-windows` |
| Console | `app/Console/Commands/EvaluateQuestionRemediationWindowsCommand.php` | `questions:evaluate-remediation-windows` |
| Schedule | `routes/console.php` | Hourly schedule for the three `questions:evaluate-*` commands |
| Frontend submit | `resources/js/pages/welcome.tsx` | Body-only question submission from home page |
| Frontend list | `resources/js/pages/questions/index.tsx` | My Questions table and actions |
| Frontend edit | `resources/js/pages/questions/edit.tsx` | Completion workflow, SweetAlert2 confirm, ReactSelect fields |
| Frontend public | `resources/js/pages/questions/browse.tsx` | Public questions list |
| Frontend public | `resources/js/pages/questions/show.tsx` | Question detail, official responses, support upvote, response vote UI |
| Frontend dashboard | `resources/js/pages/dashboard.tsx` | Official action queue (`officialActionQuestions`) |
| Routes | `routes/web.php` | `questions.store`, `questions.official-responses.store`, browse/show, votes, resource edit |
| Tests | `tests/Feature/QuestionTest.php` | Status transition, visibility, ownership, CRUD behaviors |
| Tests | `tests/Feature/QuestionLifecycleTest.php` | Threshold escalation, deadlines, validation window, electorate 403 |
| Tests | `tests/Feature/VoteTest.php` | Votes require aligned geography with question/role |

## Model Details

`Question` includes:

- Fillable: `body`, `user_id`, `official_role_id`, `status`, `effective_area`, `province_id`, `city_id`, `visits`, plus accountability timestamps: `response_deadline_at`, `response_validation_ends_at`, `second_response_deadline_at`, `remediation_review_ends_at`, `threshold_met_at`, `second_response_posted_at`
- Casts: `effective_area` => `EffectiveArea::class`, `status` => `QuestionStatus::class`, datetime casts for the accountability fields
- Helper: `isComplete(): bool` returns `status !== QuestionStatus::Incomplete`

Primary relations:

- `user()` belongs to `User`
- `officialRole()` belongs to `OfficialRole`
- `province()` belongs to `Province`
- `city()` belongs to `City`
- `questionResponses()` has many `QuestionResponse`

## Question Status Workflow

`QuestionStatus` enum cases (see `app/Enums/QuestionStatus.php`):

| Case | Typical meaning |
|------|-----------------|
| `Incomplete` | Draft; not yet submitted with role/scope |
| `Pending` | Complete question; accumulating support upvotes |
| `ForRoleUserAction` | Support threshold met; official must respond by `response_deadline_at` |
| `NeedPeopleValidateResponse` | Primary official response exists; public validation window (`response_validation_ends_at`) |
| `ForRoleUserSecondAction` | Remediation: official may post follow-up within `second_response_deadline_at`, then review via `remediation_review_ends_at` |
| `RoleUserActionsAccepted` | Reserved / legacy naming in enum |
| `RoleUserActionsNotAccepted` | Terminal: missed deadline or failed remediation path |
| `Done` | Terminal: accepted outcome after validation or quorum rules |

Detailed transitions, quorum math, and response voting are documented in `.cursor/skills/question-responses/SKILL.md`.

### Draft and completion flow (unchanged)

1. Homepage creates a draft question with body only.
2. `QuestionController::store()` saves with `status = Incomplete` and `official_role_id = null`.
3. User is redirected to `questions.edit`.
4. User completes required fields (`official_role_id`, `effective_area`, and scope IDs when required).
5. On first successful edit, status transitions from `Incomplete` to `Pending`.
6. Once no longer incomplete, question body is locked and cannot be updated.

### Accountability flow (after Pending)

1. While `Pending`, question **support** upvotes are counted against the registered electorate for the question’s scope (`ElectorateScope`). When the count reaches the role’s mandatory response threshold, `VoteObserver` + `QuestionLifecycleService::maybeEscalateToMandatoryResponse()` sets `ForRoleUserAction` and `response_deadline_at` (from `OfficialRole::response_deadline_days`).
2. An assigned official posts a **primary** response via `OfficialQuestionResponseController` → `NeedPeopleValidateResponse` and `response_validation_ends_at` (default 30 days, `QuestionLifecycleService::VALIDATION_WINDOW_DAYS`).
3. Scheduled commands (`routes/console.php`, hourly) evaluate expired response deadlines, validation windows, and remediation windows. See question-responses skill for outcomes (`Done`, `ForRoleUserSecondAction`, `RoleUserActionsNotAccepted`).

## User geography and voting

Voters must sit in the **same electorate** as the question (`EffectiveArea::Public` / `Province` / `City` vs user `country_id` / `province_id` / `city_id`). Enforcement lives in `VoteController` + `ElectorateScope::userMatchesQuestionElectorate()`. Tests that post votes should align `User` geography with `OfficialRole` and question scope (see `VoteTest`, `QuestionLifecycleTest`).

## Validation Rules by Mode

### Store (`StoreQuestionRequest`)

- `body`: `required|string|max:1000`
- No `official_role_id` on home submission.

### Update (`UpdateQuestionRequest`)

- `official_role_id`: always required.
- `official_role_id`: additionally validated against role window state using a closure rule:
  - loads `OfficialRole` by ID
  - calls `OfficialRole::isWindowOpen()`
  - fails with `The selected official role is not in an open question window.` when closed
- `effective_area`: required enum.
- `province_id` / `city_id`: conditional by effective area.
- `body`: required only when question is `Incomplete`; ignored after completion.

## Controller Window Filtering

`QuestionController::edit()` limits role choices to open windows only:

- `OfficialRole::query()->withOpenWindow()->select('id', 'name', 'slug')->orderBy('name')->get()`

## Public Question Views

- `PublicQuestionController::browse()` excludes incomplete questions.
- `PublicQuestionController::show()` blocks non-owners from incomplete questions; passes `questionResponses` and `canRespondAsOfficial`.
- `QuestionPolicy::view()` allows incomplete view only to the owner.

## Frontend Patterns in This Context

### Welcome submit page

- Uses `useForm({ body: '' })`.
- Sends only `body` to `questions.store`.
- Guest users are prompted to log in before submitting.

### Questions edit page

- Uses ReactSelect for official role, province, and city selects.
- Uses SweetAlert2 confirm dialog before first completion submit.
- Textarea is `readOnly` when `status !== 'incomplete'`.
- Uses `hasOpenRoles = officialRoleOptions.length > 0` to control role availability UI.
- If no roles are open: shows `t('questions.no_open_windows')`, disables role select and submit.
- Translation key `questions.no_open_windows` in `lang/en/app.php` and `lang/fa/app.php`.

### Public show page

- Support upvote uses `voteable_type: 'question'`.
- Official responses and accept / not-satisfied voting: see question-responses skill and `questions/show.tsx`.

## Routes

Key named routes:

- `questions.store`
- `questions.browse`
- `questions.show`
- `questions.index`
- `questions.edit`
- `questions.update`
- `questions.destroy`
- `questions.official-responses.store`
- `votes.toggle` — `POST /votes/{voteable_type}/{voteable_id}` (e.g. `question`, `question_response`)

Prefer Wayfinder-generated imports from `@/routes/questions` and `@/actions/...`.

## Extending Question Fields

1. Add migration.
2. Update `Question` fillable/casts.
3. Update `StoreQuestionRequest` and `UpdateQuestionRequest`.
4. Update `QuestionController` store/update payload logic.
5. Update `QuestionFactory`.
6. Update relevant Inertia pages (`welcome`, `questions/edit`, `questions/index`, public pages, dashboard if needed).
7. If lifecycle-affected, update `QuestionLifecycleService` and tests in `QuestionLifecycleTest`.
8. Update feature tests.
9. Run `vendor/bin/sail bin pint --dirty --format agent`.
10. Run minimum affected tests with `vendor/bin/sail artisan test --compact ...`.

## Testing Conventions

- Use `User::factory()->create()` for auth context (factory sets geography for electorate tests).
- Use `beforeEach` role setup where needed (e.g. `$this->officialRole` in `QuestionTest`).
- Vote-related HTTP tests: align `OfficialRole::country_id` (or province/city) with the acting user for `EffectiveArea::Public` (or province/city questions).
- Explicitly test:
  - store creates `incomplete` and redirects to edit,
  - incomplete → pending transition on first valid update,
  - pending body lock behavior,
  - incomplete hidden from public browse,
  - non-owner forbidden from viewing incomplete question,
  - edit page includes only roles with currently-open windows,
  - continuously-open roles are always available in edit role list,
  - update rejects closed-window role selection with `official_role_id` validation error.
