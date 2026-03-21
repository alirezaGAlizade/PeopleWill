---
name: vote-system
description: Polymorphic vote system for NPAP models with support for upvote-only and upvote/downvote resources, including audit-friendly vote logs and per-user vote state.
---

# Vote System

## When to Activate

Use this skill whenever you:

- Add voting to a model (`Question`, `Case`, `Response`, etc.)
- Change vote rules (upvote-only vs upvote/downvote)
- Update vote endpoints, validation, or UI vote actions
- Debug vote counts, toggle behavior, or per-user vote state
- Add or update vote-related tests

## Architecture

The platform uses a **polymorphic vote table** so one vote subsystem can be reused across multiple models.

## Key Files

| Layer | File | Purpose |
|-------|------|---------|
| Enum | `app/Enums/VoteType.php` | Vote types (`up`, `down`) |
| Trait | `app/Models/Concerns/HasVotes.php` | Shared vote relations + toggle logic |
| Model | `app/Models/Vote.php` | Stores voter, target model, vote type |
| Controller | `app/Http/Controllers/VoteController.php` | Authenticated vote toggle endpoint |
| Request | `app/Http/Requests/ToggleVoteRequest.php` | Validates vote payload |
| Migration | `database/migrations/2026_03_20_234735_create_votes_table.php` | Polymorphic votes table |
| Routes | `routes/web.php` | `POST /votes/{voteable_type}/{voteable_id}` |

## Data Model

`votes` table columns:

- `user_id` (FK to users)
- `voteable_type` (morph class)
- `voteable_id` (morph id)
- `type` (`up` or `down`)
- timestamps

Constraint:

- unique on (`user_id`, `voteable_type`, `voteable_id`) to enforce one active vote per user per resource

## Model Opt-In

To add voting to a model:

1. Add `use HasVotes;` to the model.
2. If the model must be upvote-only, define `protected bool $allowDownvotes = false;`.
3. Use `withCount('upvotes')` / `withCount('downvotes')` in queries as needed.

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

1. Resolves target model from route params
2. Validates request (`type`)
3. Rejects unsupported vote types for the target model
4. Applies toggle logic
5. Redirects back

## Testing Checklist

At minimum, add feature tests for:

- authenticated upvote
- guest blocked from voting
- upvote toggle off on second click
- unsupported downvote rejected on upvote-only models
- count correctness for target model relations
