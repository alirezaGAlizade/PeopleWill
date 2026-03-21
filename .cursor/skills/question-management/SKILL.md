---
name: question-management
description: Civic question submission and management in the NPAP platform. Activate when creating, editing, listing, deleting, or extending questions; working with the Question model, QuestionController, QuestionPolicy, StoreQuestionRequest, UpdateQuestionRequest, or QuestionFactory; adding new question fields or validation rules; wiring the welcome page question form; or building the authenticated "My Questions" dashboard (index/edit/delete).
---

# Question Management

## Architecture

Questions are the entry point of the civic accountability workflow. A citizen submits a question, which later becomes a **Case** once it gathers enough support.

### Bounded context

Questions live in the **Civic Cases** bounded context per the DDD rules in `npap-project.mdc`.

## Key Files

| Layer | File | Purpose |
|-------|------|---------|
| Model | `app/Models/Question.php` | Eloquent model with `SoftDeletes`, `HasFactory` |
| Policy | `app/Policies/QuestionPolicy.php` | Ownership for `view` / `update` / `delete` / `restore`; `viewAny` / `create` for authenticated listing & creation |
| Migration | `database/migrations/2026_03_20_181542_create_questions_table.php` | Schema: `id`, `user_id` (FK), `body` (text), timestamps, `softDeletes` |
| Factory | `database/factories/QuestionFactory.php` | Default: `user_id` via `User::factory()`, `body` via `fake()->paragraph()` |
| Controller | `app/Http/Controllers/QuestionController.php` | `store` (welcome); `index`, `edit`, `update`, `destroy` (My Questions) |
| Validation | `app/Http/Requests/StoreQuestionRequest.php` | `body`: `required\|string\|max:1000`; authorize: user present |
| Validation | `app/Http/Requests/UpdateQuestionRequest.php` | Same `body` rules; authorize: `can('update', $question)` |
| Routes | `routes/web.php` | `POST /questions` → `questions.store` (`auth`); resource `index` / `edit` / `update` / `destroy` → `auth` + `verified` |
| Frontend (submit) | `resources/js/pages/welcome.tsx` | `useForm({ body })` → POST `store` via Wayfinder |
| Frontend (manage) | `resources/js/pages/questions/index.tsx` | Table, pagination (10), edit link, delete + confirm dialog |
| Frontend (manage) | `resources/js/pages/questions/edit.tsx` | Textarea for `body`, `useForm` + `put` to `update` |
| Nav | `resources/js/components/app-sidebar.tsx` | "My Questions" → `questions.index` (Wayfinder `index` from `@/routes/questions`) |
| Base controller | `app/Http/Controllers/Controller.php` | Uses `AuthorizesRequests` so controllers can call `$this->authorize()` |
| Tests | `tests/Feature/QuestionTest.php` | Store validation, soft delete, index pagination & isolation, edit/update/destroy + 403 for other users’ questions |

## Model Details

```php
// app/Models/Question.php
use SoftDeletes, HasFactory;

protected $fillable = ['body', 'user_id'];

public function user(): BelongsTo  // belongs to User
```

The inverse relationship on `User`:

```php
// app/Models/User.php
public function questions(): HasMany
```

## Policy

`QuestionPolicy` ensures users only manage their own rows (`user_id` match) for `view`, `update`, `delete`, and `restore`. `viewAny` is `true` so authenticated users can open the index (the query is still scoped to `$request->user()->questions()`). `forceDelete` is denied.

## Routes

```php
// Submission from home (any authenticated user)
Route::middleware(['auth'])->group(function () {
    Route::post('questions', [QuestionController::class, 'store'])->name('questions.store');
});

// My Questions (verified users only, same group as dashboard)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('questions', QuestionController::class)
        ->only(['index', 'edit', 'update', 'destroy']);
});
```

Named routes: `questions.index`, `questions.edit`, `questions.update`, `questions.destroy`, `questions.store`.

Wayfinder generates `resources/js/routes/questions/index.ts` — import e.g. `store`, `index`, `edit`, `update`, `destroy` from `@/routes/questions`.

## Frontend: Welcome (submit)

```tsx
const questionForm = useForm({ body: '' });

questionForm.post(storeQuestion.url(), {
    preserveScroll: true,
    onSuccess: () => questionForm.reset('body'),
});
```

- **Logged in**: input is editable, submit POSTs to `questions.store`.
- **Guest**: input is `readOnly`, clicking shows a hint to log in (`welcome.login_to_ask` translation key).
- Validation errors render below the input via `questionForm.errors.body`.

## Frontend: My Questions (list & edit)

- **Index**: `AppLayout`, breadcrumbs, table with truncated `body`, submitted date, Edit (`edit.url(question.id)`) and Delete (`router.delete(destroy.url(id))`) with a confirmation dialog; pagination via `prev_page_url` / `next_page_url` and page labels (10 per page from the backend).
- **Edit**: `useForm({ body: question.body })`, `form.put(update.url(question.id))`, `InputError` for `body`.

## Adding New Fields to Questions

1. Create a migration adding the column to the `questions` table.
2. Add the field to `Question::$fillable`.
3. Update `StoreQuestionRequest::rules()` and `UpdateQuestionRequest::rules()` with validation.
4. Update `QuestionController::store()` and `QuestionController::update()` to persist the new field.
5. Update `QuestionFactory::definition()` with a default value.
6. Update the welcome form and `resources/js/pages/questions/edit.tsx` (and index column if it should appear in the table).
7. Add/update tests in `tests/Feature/QuestionTest.php`.
8. Run `vendor/bin/sail bin pint --dirty --format agent`.
9. Regenerate Wayfinder / Vite as needed: `vendor/bin/sail yarn run build` (so new pages stay in the manifest for tests and production).

## Testing Conventions

- Use `User::factory()->create()` for authenticated user setup (factory verifies email by default — required for `questions.index` and related routes).
- Use `Question::factory()->create()` or `Question::factory()->for($user)->create()` for seeding questions.
- Assert database state with `assertDatabaseHas` / `assertSoftDeleted`.
- Assert validation with `assertSessionHasErrors`.
- Guest tests assert redirect to `route('login')` where middleware is `auth`.
- Use `assertForbidden()` when acting as another user for `edit` / `update` / `destroy`.
- Inertia: `Inertia\Testing\AssertableInertia` for `questions/index` and `questions/edit` props.
