---
name: question-responses
description: Official answers to civic questions in NPAP. Activate when working on QuestionResponse model, sequence 1 vs 2, HasVotes on responses, QuestionLifecycleService transitions, Artisan evaluate commands, OfficialQuestionResponseController, StoreOfficialQuestionResponseRequest, QuestionPolicy respondAsOfficial, public show UI for responses and evaluation votes, i18n keys, or tests for validation and remediation windows.
---

# Question Responses (Official Answers)

## Purpose

`QuestionResponse` records the **primary** (sequence `1`) and optional **follow-up** (sequence `2`) text answers from users assigned to the question’s `OfficialRole`. Citizens evaluate the **primary** response via upvote (accept) and downvote (not satisfied) within rules driven by `OfficialRole` quorum fields, `ElectorateScope`, and `QuestionLifecycleService`. This skill complements `.cursor/skills/question-management/SKILL.md` and `.cursor/skills/vote-system/SKILL.md`.

## Key Files

| Layer | File | Purpose |
|-------|------|---------|
| Model | `app/Models/QuestionResponse.php` | `HasVotes`, `$allowDownvotes = true`; belongs to `Question`, `User` |
| Model | `app/Models/Question.php` | `questionResponses()` relationship |
| Policy | `app/Policies/QuestionPolicy.php` | `respondAsOfficial()` — role membership, status, sequence, second-response deadline |
| Controller | `app/Http/Controllers/OfficialQuestionResponseController.php` | `store()` dispatches primary vs second via lifecycle service |
| Request | `app/Http/Requests/StoreOfficialQuestionResponseRequest.php` | Validates `body`; authorizes via `respondAsOfficial` |
| Service | `app/Services/QuestionLifecycleService.php` | Threshold escalation, `recordPrimaryResponse`, `recordSecondResponse`, validation/remediation finalization, scheduled processors |
| Service | `app/Services/ElectorateScope.php` | Electorate counts, satisfaction, turnout, downvote rejection vs population |
| Observer | `app/Observers/VoteObserver.php` | Escalates **Question** support only (not `QuestionResponse`) |
| Migration | `database/migrations/2026_03_22_152307_create_question_responses_table.php` | `question_id`, `user_id`, `body`, `sequence`; unique `(question_id, sequence)` |
| Factory | `database/factories/QuestionResponseFactory.php` | Test data |
| Commands | `app/Console/Commands/EvaluateQuestionResponseDeadlinesCommand.php` | `questions:evaluate-response-deadlines` |
| Commands | `app/Console/Commands/EvaluateQuestionValidationWindowsCommand.php` | `questions:evaluate-validation-windows` |
| Commands | `app/Console/Commands/EvaluateQuestionRemediationWindowsCommand.php` | `questions:evaluate-remediation-windows` |
| Schedule | `routes/console.php` | Hourly schedule for the three commands above |
| Public UI | `resources/js/pages/questions/show.tsx` | Lists responses; accept / not-satisfied buttons; official response form when `canRespondAsOfficial` |
| Actions | `resources/js/actions/App/Http/Controllers/OfficialQuestionResponseController.ts` | Wayfinder `store` for `POST .../official-responses` |
| i18n | `lang/en/app.php`, `lang/fa/app.php` | Keys under `questions.*` e.g. `official_response_primary`, `accept_response`, `reject_response` |
| Tests | `tests/Feature/QuestionLifecycleTest.php` | Escalation, deadlines, validation satisfaction, electorate 403 |

## Sequence Rules

- **Sequence `1`**: Primary official response. At most one per question (enforced by unique `(question_id, sequence)`).
- **Sequence `2`**: Follow-up / remediation attachment. At most one per question.
- Authoring user is stored on `user_id` (the official who posted).

## Voting Semantics (on `QuestionResponse`)

- **Upvote**: Citizen accepts the official’s answer (counts toward “satisfied” share of electorate vs `ElectorateScope::satisfactionFromUpvoteRatio`).
- **Downvote**: Citizen is not satisfied (counts toward rejection path vs `response_rejection_downvote_percent` when turnout meets `participation_quorum_percent`).
- Toggle behavior is standard `HasVotes` (switching or clearing vote).

Evaluation uses **votes on the primary response** (`sequence === 1`) for validation and remediation outcomes; see `QuestionLifecycleService::finalizeValidationWindow` and `finalizeRemediationReview`.

## Status Coupling (when officials may post)

Aligned with `QuestionPolicy::respondAsOfficial()`:

| Question status | Allowed action |
|-----------------|----------------|
| `ForRoleUserAction` | Post **primary** if no `sequence === 1` row exists |
| `ForRoleUserSecondAction` | Post **second** if no `sequence === 2` row and `second_response_deadline_at` not passed |

The user must be attached to the question’s `official_role_id` via `official_role_user`.

## Lifecycle Service (reference)

Not exhaustive—read `app/Services/QuestionLifecycleService.php` when changing behavior.

- **Constants**: `VALIDATION_WINDOW_DAYS` (30), `SECOND_RESPONSE_WINDOW_DAYS` (7), `REMEDIATION_REVIEW_DAYS` (7).
- **maybeEscalateToMandatoryResponse**: `Pending` + support threshold → `ForRoleUserAction` + `response_deadline_at`.
- **recordPrimaryResponse**: creates sequence `1`, sets `NeedPeopleValidateResponse` + `response_validation_ends_at`.
- **recordSecondResponse**: creates sequence `2`, sets `second_response_posted_at`, `remediation_review_ends_at`, clears `second_response_deadline_at`.
- **processExpiredResponseDeadlines**: missed primary by deadline → `RoleUserActionsNotAccepted`.
- **processExpiredValidationWindows**: end of validation window → `Done` or `ForRoleUserSecondAction` or `Done` per quorum rules.
- **processExpiredRemediationWindows**: missed second response window or end of remediation review → `RoleUserActionsNotAccepted` or `Done`.

## HTTP

- **Route**: `POST` `questions/{question}/official-responses`, name `questions.official-responses.store`.
- **Votes on responses**: `POST` `/votes/question_response/{id}` with `type` `up` | `down` (see vote-system skill).

## Extending

1. Prefer changing `QuestionLifecycleService` and policies before adding controller logic.
2. Keep electorate and role thresholds in `ElectorateScope` / `OfficialRole` / tests aligned.
3. Update Inertia props in `PublicQuestionController::show` if new per-response data is needed.
4. Add/adjust `tests/Feature/QuestionLifecycleTest.php` and run `vendor/bin/sail bin pint --dirty --format agent`.

## Testing Conventions

- Use factories with consistent geography for electorate (`User`, `OfficialRole`, `Question` with `EffectiveArea`).
- Use `Carbon::setTestNow()` when asserting scheduled command behavior.
- Cover: threshold → `ForRoleUserAction`; deadline expiry; validation window → `Done` when majority of electorate accepts primary response; voter outside electorate → 403 on votes.
