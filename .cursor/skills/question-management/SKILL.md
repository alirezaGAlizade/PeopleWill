---
name: question-management
description: Civic question submission and lifecycle management in NPAP. Activate when creating, editing, listing, deleting, or extending questions; working with Question status workflow, QuestionController, PublicQuestionController, QuestionPolicy, StoreQuestionRequest, UpdateQuestionRequest, QuestionFactory, welcome question submission, My Questions pages, window-aware role filtering, or no-open-window UX behavior.
---

# Question Management

## Architecture

Questions are the entry point of the civic accountability workflow in the Civic Cases bounded context.

## Key Files

| Layer | File | Purpose |
|-------|------|---------|
| Enum | `app/Enums/QuestionStatus.php` | Status lifecycle (`incomplete` -> `pending` -> later states) |
| Model | `app/Models/Question.php` | Fillable fields, enum casts, relations, `isComplete()` |
| Policy | `app/Policies/QuestionPolicy.php` | Owner-only visibility for incomplete questions; ownership checks for update/delete |
| Controller | `app/Http/Controllers/QuestionController.php` | Authenticated create/edit/update/delete for user-owned questions |
| Public controller | `app/Http/Controllers/PublicQuestionController.php` | Public browse/show with incomplete-question restrictions |
| Validation | `app/Http/Requests/StoreQuestionRequest.php` | Body-only creation validation |
| Validation | `app/Http/Requests/UpdateQuestionRequest.php` | Status-aware update validation and area rules |
| Enum dependency | `app/Enums/WindowPlan.php` | Official role window cadence used by role availability logic |
| Enum dependency | `app/Enums/WindowDuration.php` | Official role duration used by role availability logic |
| Migration | `database/migrations/2026_03_20_181542_create_questions_table.php` | Base questions schema |
| Migration | `database/migrations/2026_03_22_002608_add_official_role_id_to_questions_table.php` | Nullable `official_role_id` FK |
| Migration | `database/migrations/2026_03_22_005438_add_status_to_questions_table.php` | `status` column defaulting to `incomplete` |
| Factory | `database/factories/QuestionFactory.php` | Defaults include `status = pending` for visible test records |
| Frontend submit | `resources/js/pages/welcome.tsx` | Body-only question submission from home page |
| Frontend list | `resources/js/pages/questions/index.tsx` | My Questions table and actions |
| Frontend edit | `resources/js/pages/questions/edit.tsx` | Completion workflow, SweetAlert2 confirm, ReactSelect fields |
| Frontend public | `resources/js/pages/questions/browse.tsx` | Public questions list |
| Frontend public | `resources/js/pages/questions/show.tsx` | Public question detail |
| Routes | `routes/web.php` | `questions.store`, public browse/show, resource edit routes |
| Tests | `tests/Feature/QuestionTest.php` | Status transition, visibility, ownership, CRUD behaviors |

## Model Details

`Question` currently includes:

- Fillable: `body`, `user_id`, `official_role_id`, `status`, `effective_area`, `province_id`, `city_id`, `visits`
- Casts: `effective_area` => `EffectiveArea::class`, `status` => `QuestionStatus::class`
- Helper: `isComplete(): bool` returns `status !== QuestionStatus::Incomplete`

Primary relations:

- `user()` belongs to `User`
- `officialRole()` belongs to `OfficialRole`
- `province()` belongs to `Province`
- `city()` belongs to `City`

## Question Status Workflow

`QuestionStatus` enum cases:

- `Incomplete`
- `Pending`
- `ForRoleUserAction`
- `RoleUserActionsAccepted`
- `RoleUserActionsNotAccepted`
- `Done`

Current workflow implementation:

1. Homepage creates a draft question with body only.
2. `QuestionController::store()` saves with `status = Incomplete` and `official_role_id = null`.
3. User is redirected to `questions.edit`.
4. User completes required fields (`official_role_id`, `effective_area`, and scope IDs when required).
5. On first successful edit, status transitions from `Incomplete` to `Pending`.
6. Once no longer incomplete, question body is locked and cannot be updated.

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

`QuestionController::edit()` now limits role choices to open windows only:

- `OfficialRole::query()->withOpenWindow()->select('id', 'name', 'slug')->orderBy('name')->get()`

This keeps the edit form synchronized with current role window availability.

## Public Question Views

Visibility rules for incomplete drafts:

- `PublicQuestionController::browse()` excludes incomplete questions.
- `PublicQuestionController::show()` blocks non-owners from incomplete questions.
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
- If no roles are open:
  - shows `t('questions.no_open_windows')`,
  - disables the official role select (`isDisabled`),
  - disables the submit button.
- Translation key `questions.no_open_windows` exists in:
  - `lang/en/app.php`
  - `lang/fa/app.php`

## Routes

Key named routes:

- `questions.store`
- `questions.browse`
- `questions.show`
- `questions.index`
- `questions.edit`
- `questions.update`
- `questions.destroy`

Prefer Wayfinder-generated imports from `@/routes/questions`.

## Extending Question Fields

1. Add migration.
2. Update `Question` fillable/casts.
3. Update `StoreQuestionRequest` and `UpdateQuestionRequest`.
4. Update `QuestionController` store/update payload logic.
5. Update `QuestionFactory`.
6. Update relevant Inertia pages (`welcome`, `questions/edit`, `questions/index`, public pages if needed).
7. Update feature tests.
8. Run `vendor/bin/sail bin pint --dirty --format agent`.
9. Run minimum affected tests with `vendor/bin/sail artisan test --compact ...`.

## Testing Conventions

- Use `User::factory()->create()` for auth context.
- Use `beforeEach` role setup where needed (e.g. `$this->officialRole` in `QuestionTest`).
- Explicitly test:
  - store creates `incomplete` and redirects to edit,
  - incomplete -> pending transition on first valid update,
  - pending body lock behavior,
  - incomplete hidden from public browse,
  - non-owner forbidden from viewing incomplete question,
  - edit page includes only roles with currently-open windows,
  - continuously-open roles are always available in edit role list,
  - update rejects closed-window role selection with `official_role_id` validation error.
