---
name: vote-system
description: Polymorphic vote system for NPAP models with upvote-only and upvote/downvote resources, electorate authorization (ElectorateScope), Question vs QuestionResponse voteables, VoteObserver escalation for question support, audit-friendly vote logs, per-user vote state, and debugging 403 on votes.
---

# Vote System

## When to Activate

Use this skill whenever you:

- Add voting to a model (`Question`, `QuestionResponse`, future morph targets)
- Change vote rules (upvote-only vs upvote/downvote)
- Update vote endpoints, validation, or UI vote actions
- Work on **electorate-scoped** voting or debug **403** responses from `VoteController`
- Change `VoteObserver` behavior for question support thresholds
- Debug vote counts, toggle behavior, or per-user vote state
- Add or update vote-related tests

## Architecture

The platform uses a **polymorphic vote table** so one vote subsystem can be reused across multiple models. **Question** votes (support) and **QuestionResponse** votes (accept / not satisfied) share the same `votes` table and `VoteController` route, with different authorization and downvote rules.

## Key Files

| Layer | File | Purpose |
|-------|------|---------|
| Enum | `app/Enums/VoteType.php` | Vote types (`up`, `down`) |
| Trait | `app/Models/Concerns/HasVotes.php` | Shared vote relations + toggle logic |
| Model | `app/Models/Vote.php` | Stores voter, target model, vote type |
| Model | `app/Models/Question.php` | Support votes; `$allowDownvotes = false` (upvote-only) |
| Model | `app/Models/QuestionResponse.php` | Evaluation votes; `$allowDownvotes = true` |
| Service | `app/Services/ElectorateScope.php` | `userMatchesQuestionElectorate()` for vote authorization |
| Controller | `app/Http/Controllers/VoteController.php` | Resolves voteable; enforces electorate; toggles vote |
| Observer | `app/Observers/VoteObserver.php` | On vote **saved** for a `Question`, may escalate pending → mandatory response |
| Request | `app/Http/Requests/ToggleVoteRequest.php` | Validates vote payload |
| Provider | `app/Providers/AppServiceProvider.php` | Registers `Vote::observe(VoteObserver::class)` |
| Migration | `database/migrations/2026_03_20_234735_create_votes_table.php` | Polymorphic votes table |
| Routes | `routes/web.php` | `POST /votes/{voteable_type}/{voteable_id}` (`votes.toggle`) |

## Data Model

`votes` table columns:

- `user_id` (FK to users)
- `voteable_type` (morph class — full class name, e.g. `App\Models\Question`)
- `voteable_id` (morph id)
- `type` (`up` or `down`)
- timestamps

Constraint:

- unique on (`user_id`, `voteable_type`, `voteable_id`) to enforce one active vote per user per resource

## Route parameter `voteable_type`

`VoteController::resolveVoteable()` maps **short** route keys to Eloquent classes:

| `voteable_type` (URL segment) | Model class |
|-------------------------------|-------------|
| `question` | `App\Models\Question` |
| `question_response` | `App\Models\QuestionResponse` |

The database still stores the **full** morph class name on `votes.voteable_type`.

## Electorate authorization

After resolving the voteable, `VoteController` calls `ElectorateScope::userMatchesQuestionElectorate()`:

- **`Question`**: the acting user must be in the question’s electorate (derived from `EffectiveArea` + `official_role` country / question province / question city vs user `country_id`, `province_id`, `city_id`).
- **`QuestionResponse`**: loads parent `question` and applies the **same** check.

If the check fails, the controller returns **403**. Frontend must pass `voteable_type` and `voteable_id` consistent with Wayfinder (`question`, `question_response`).

## Model Opt-In

1. Add `use HasVotes;` to the model.
2. If the model must be upvote-only, define `protected bool $allowDownvotes = false;` (`Question`).
3. For evaluation with downvotes, set `$allowDownvotes = true` (`QuestionResponse`).
4. Use `withCount('upvotes')` / `withCount('downvotes')` in queries as needed.

## Toggle Behavior

`HasVotes::toggleVote(User $user, VoteType $type)` works as:

- same existing type: remove vote (toggle off)
- different existing type: update vote type (switch)
- no existing vote: create vote

## Supported Vote Types

`HasVotes::supportsVoteType()` enforces whether a model allows downvotes:

- upvote always allowed
- downvote allowed only when `allowsDownvotes()` returns `true`

## Controller Contract

`VoteController::toggle()`:

1. Resolves target model from `voteable_type` + `voteable_id`
2. Validates request (`type`)
3. Rejects unsupported vote types for the target model (422)
4. Authorizes voter against parent question electorate (403 via `ElectorateScope`)
5. Applies toggle logic
6. Redirects back

`VoteObserver::saved()` does **not** run on `QuestionResponse` votes; it only calls `QuestionLifecycleService::maybeEscalateToMandatoryResponse()` when the voteable is a `Question`.

## Testing Checklist

Feature tests should cover:

- authenticated upvote on a `Question` with **aligned** geography (`OfficialRole` + `EffectiveArea` + user location)
- guest blocked from voting
- upvote toggle off on second click
- unsupported downvote rejected on `Question` (422)
- **403** when voter is outside the electorate (`QuestionLifecycleTest`)
- count correctness for `upvotes` / `downvotes` on the target model

See `tests/Feature/VoteTest.php` and `tests/Feature/QuestionLifecycleTest.php`.
