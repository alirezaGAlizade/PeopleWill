---
name: official-role
description: Official government role management in the NPAP platform. Activate when creating, editing, querying, seeding, or testing OfficialRole records; mandatory response threshold percent, response deadline days, participation quorum, response rejection downvote percent; model relationships; official_role_user assignments; attaching questions to official roles; window plan scheduling, open/close logic, CloseExpiredWindows command, or linking roles to QuestionLifecycleService and ElectorateScope.
---

# Official Role Management

## Purpose

Official roles represent public offices (for example President, Mayor, City Council) used by questions and user-role assignments. Each role can define **accountability parameters** (support threshold, deadlines, quorum rules) consumed by `QuestionLifecycleService` and `ElectorateScope` when evaluating question and response outcomes. Exact formulas and status transitions live in `.cursor/skills/question-responses/SKILL.md`.

## Key Files

| Layer | File | Purpose |
|-------|------|---------|
| Enum | `app/Enums/MandatoryResponseThresholdPercent.php` | Backed enum `Percent3`–`Percent6` for mandatory support threshold |
| Model | `app/Models/OfficialRole.php` | Slugged role entity with geographic scope, window fields, accountability fields |
| Inverse model | `app/Models/User.php` | `officialRoles()` many-to-many relationship |
| Inverse model | `app/Models/Question.php` | `officialRole()` nullable belongs-to relationship |
| Service | `app/Services/QuestionLifecycleService.php` | Uses role thresholds and deadlines for lifecycle transitions |
| Service | `app/Services/ElectorateScope.php` | Uses role `country_id` for public-scope electorate |
| Enum | `app/Enums/WindowPlan.php` | Window cadence enum (`Continuously`, `Every6Months`, …) |
| Enum | `app/Enums/WindowDuration.php` | Window duration enum (`SevenDays = 7`) |
| Command | `app/Console/Commands/CloseExpiredWindows.php` | Daily command that closes expired periodic windows |
| Console schedule | `routes/console.php` | Schedules `app:close-expired-windows` at `00:01`; hourly `questions:evaluate-*` (lifecycle) |
| Factory | `database/factories/OfficialRoleFactory.php` | Test data defaults including accountability fields |
| Seeder | `database/seeders/OfficialRoleSeeder.php` | Baseline roles with README-aligned thresholds |
| Migration | `database/migrations/2026_03_22_002550_create_official_roles_table.php` | Official roles table schema |
| Migration | `database/migrations/2026_03_22_124305_add_window_fields_to_official_roles_table.php` | Window schedule fields |
| Migration | `database/migrations/2026_03_22_152146_add_accountability_fields_to_official_roles_table.php` | Mandatory threshold, deadline days, quorum, rejection percent |
| Migration | `database/migrations/2026_03_22_002559_create_official_role_user_table.php` | User-role pivot schema |
| Migration | `database/migrations/2026_03_22_002608_add_official_role_id_to_questions_table.php` | Nullable role FK on questions |
| Tests | `tests/Feature/OfficialRoleTest.php` | Relationship, slug, and seeder behavior tests |
| Tests | `tests/Feature/CloseExpiredWindowsCommandTest.php` | Expired-window command behavior tests |

## Model Details

`OfficialRole` includes:

- Traits: `HasFactory`, `HasSlug`
- Fillable:
  - `name`
  - `slug`
  - `country_id`
  - `province_id`
  - `city_id`
  - `window_plan`
  - `open_window_duration`
  - `last_window_close_date`
  - `mandatory_response_threshold`
  - `response_deadline_days`
  - `participation_quorum_percent`
  - `response_rejection_downvote_percent`
- Casts:
  - `window_plan` => `WindowPlan::class`
  - `open_window_duration` => `WindowDuration::class`
  - `last_window_close_date` => `datetime`
  - `mandatory_response_threshold` => `MandatoryResponseThresholdPercent::class`
- Slug behavior:
  - generated from `name`
  - saved to `slug`

Relationships:

- `users()` -> `BelongsToMany<User>`
- `country()` -> `BelongsTo<Country>`
- `province()` -> `BelongsTo<Province>`
- `city()` -> `BelongsTo<City>`
- `questions()` -> `HasMany<Question>`

### Accountability columns (migration defaults)

| Column | Purpose |
|--------|---------|
| `mandatory_response_threshold` | Integer 3–6 in DB; cast to `MandatoryResponseThresholdPercent` (default **5** in migration) |
| `response_deadline_days` | Days after threshold met for official to post primary response (default **14** in migration; seeder sets **7** for seeded roles) |
| `participation_quorum_percent` | Minimum turnout (% of electorate voting on primary response) before rejection/remediation rules apply (default **10**) |
| `response_rejection_downvote_percent` | Downvotes as % of electorate threshold for rejection path when quorum met (default **10**) |

## Window Scheduling

Window availability is driven by `window_plan`, `open_window_duration`, and `last_window_close_date`.

### Enums

- `WindowPlan`:
  - `Continuously`
  - `Every6Months`
  - `Every4Months`
  - `Every3Months`
  - `Every2Months`
- `WindowPlan::monthsInterval()`:
  - returns `null` for `Continuously`
  - returns `6`, `4`, `3`, `2` for periodic plans
- `WindowDuration`:
  - `SevenDays = 7`

### Model Methods

- `isWindowOpen(?CarbonInterface $currentTime = null): bool`
  - `true` for `Continuously`
  - for periodic plans, `true` only when `now >= windowOpensAt` and `now < windowClosesAt`
- `windowOpensAt(): ?CarbonImmutable`
  - calculates `last_window_close_date + plan months interval`
- `windowClosesAt(): ?CarbonImmutable`
  - calculates `windowOpensAt + open_window_duration days`
- `scopeWithOpenWindow(Builder $query): void`
  - filters roles to currently-open ones by applying `isWindowOpen()` and then `whereKey(...)`

## Schema Rules

### `official_roles`

- `id`
- `name`
- `slug` (unique)
- `country_id` (nullable FK)
- `province_id` (nullable FK)
- `city_id` (nullable FK)
- `window_plan` (nullable string)
- `open_window_duration` (nullable unsigned small integer)
- `last_window_close_date` (nullable timestamp, default `CURRENT_TIMESTAMP`)
- `mandatory_response_threshold` (unsigned tinyint)
- `response_deadline_days` (unsigned small integer)
- `participation_quorum_percent` (unsigned tinyint)
- `response_rejection_downvote_percent` (unsigned tinyint)
- timestamps

All geographic FKs use `nullOnDelete()`.

### `official_role_user` pivot

- `id`
- `official_role_id` FK with `cascadeOnDelete()`
- `user_id` FK with `cascadeOnDelete()`
- timestamps
- unique pair on `[official_role_id, user_id]`

### Question linkage

`questions.official_role_id` is nullable and uses `nullOnDelete()`.

## Seeder Workflow

`OfficialRoleSeeder`:

1. Looks up one country ID.
2. Seeds baseline role names (11 roles) mapped to `[WindowPlan, MandatoryResponseThresholdPercent]` (README-style percentages, e.g. President `Percent3`, Mayor `Percent4`).
3. Uses `updateOrCreate` to remain idempotent.
4. Stores roles with:
   - `open_window_duration` => `WindowDuration::SevenDays`
   - `last_window_close_date` => `now()`
   - `mandatory_response_threshold` => per-role enum case
   - `response_deadline_days` => `7`
   - `participation_quorum_percent` => `10`
   - `response_rejection_downvote_percent` => `10`
   - null province/city scope by default.

## Lifecycle integration

`mandatory_response_threshold` and `response_deadline_days` feed `QuestionLifecycleService::maybeEscalateToMandatoryResponse()` and response deadline expiry. `participation_quorum_percent` and `response_rejection_downvote_percent` feed validation and remediation finalization together with `ElectorateScope` (turnout and downvote ratios vs registered electorate). Time windows such as `VALIDATION_WINDOW_DAYS` (30) and remediation days are class constants on `QuestionLifecycleService`, not columns on `official_roles`.

## Scheduled Command: CloseExpiredWindows

- Signature: `app:close-expired-windows`
- Schedule: `Schedule::command('app:close-expired-windows')->dailyAt('00:01')`
- Behavior:
  1. Loads periodic roles (non-`Continuously`) with complete window fields.
  2. Computes `windowClosesAt()` for each role.
  3. If `windowClosesAt() <= now()`, updates `last_window_close_date` to the calculated close date.
  4. Leaves non-expired roles untouched.

## Factory Defaults

`OfficialRoleFactory` defaults include:

- `window_plan` => `WindowPlan::Continuously`
- `open_window_duration` => `WindowDuration::SevenDays`
- `last_window_close_date` => `now()`
- `mandatory_response_threshold` => `MandatoryResponseThresholdPercent::Percent5`
- `response_deadline_days` => `14`
- `participation_quorum_percent` => `10`
- `response_rejection_downvote_percent` => `10`

## Usage Guidelines

- Prefer `OfficialRole::query()` and relationships over manual joins.
- Assign users with pivot relation methods (`attach`, `sync`, `syncWithoutDetaching`).
- Keep role names human-readable; slug is generated automatically.
- When extending role scope or accountability logic, update model, factory, seeder, migrations, `QuestionLifecycleService` / tests together.
- For question-facing role selection, prefer `scopeWithOpenWindow()` to avoid exposing closed windows.

## Testing Conventions

For OfficialRole features, ensure tests cover:

- slug generation from name,
- nullable geographic scope behavior,
- user-role pivot assignment,
- question-to-role relationship,
- seeder baseline role count and key role presence,
- window calculations for periodic vs continuous plans,
- `CloseExpiredWindows` command updating expired roles,
- `CloseExpiredWindows` command not updating non-expired or continuously-open roles.
