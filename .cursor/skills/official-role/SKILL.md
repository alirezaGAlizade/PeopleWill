---
name: official-role
description: Official government role management in the NPAP platform. Activate when creating, editing, querying, seeding, or testing OfficialRole records; working with OfficialRole model relationships; managing official_role_user assignments; attaching questions to official roles; or implementing window plan scheduling, open/close logic, and the CloseExpiredWindows command.
---

# Official Role Management

## Purpose

Official roles represent public offices (for example President, Mayor, City Council) used by questions and user-role assignments.

## Key Files

| Layer | File | Purpose |
|-------|------|---------|
| Model | `app/Models/OfficialRole.php` | Slugged role entity with geographic scope and relationships |
| Inverse model | `app/Models/User.php` | `officialRoles()` many-to-many relationship |
| Inverse model | `app/Models/Question.php` | `officialRole()` nullable belongs-to relationship |
| Enum | `app/Enums/WindowPlan.php` | Window cadence enum (`Continuously`, `Every6Months`, `Every4Months`, `Every3Months`, `Every2Months`) |
| Enum | `app/Enums/WindowDuration.php` | Window duration enum (`SevenDays = 7`) |
| Command | `app/Console/Commands/CloseExpiredWindows.php` | Daily command that closes expired periodic windows |
| Console schedule | `routes/console.php` | Schedules `app:close-expired-windows` at `00:01` |
| Factory | `database/factories/OfficialRoleFactory.php` | Test data defaults for role creation |
| Seeder | `database/seeders/OfficialRoleSeeder.php` | Baseline list of national/local official roles |
| Migration | `database/migrations/2026_03_22_002550_create_official_roles_table.php` | Official roles table schema |
| Migration | `database/migrations/2026_03_22_124305_add_window_fields_to_official_roles_table.php` | Adds window schedule fields to official roles |
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
- Casts:
  - `window_plan` => `WindowPlan::class`
  - `open_window_duration` => `WindowDuration::class`
  - `last_window_close_date` => `datetime`
- Slug behavior:
  - generated from `name`
  - saved to `slug`

Relationships:

- `users()` -> `BelongsToMany<User>`
- `country()` -> `BelongsTo<Country>`
- `province()` -> `BelongsTo<Province>`
- `city()` -> `BelongsTo<City>`
- `questions()` -> `HasMany<Question>`

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
2. Seeds baseline role names (11 roles) mapped to a `WindowPlan` case:
   - President: `Every6Months`
   - First Vice President: `Every4Months`
   - Minister: `Every3Months`
   - Head of Key Independent National Institution: `Every4Months`
   - Member of Parliament: `Every2Months`
   - Governor/Provincial Council/Mayor/City Council/County Governor: `Continuously`
3. Uses `updateOrCreate` to remain idempotent.
4. Stores roles with:
   - `open_window_duration` => `WindowDuration::SevenDays`
   - `last_window_close_date` => `now()`
   - null province/city scope by default.

## Scheduled Command: CloseExpiredWindows

- Signature: `app:close-expired-windows`
- Schedule: `Schedule::command('app:close-expired-windows')->dailyAt('00:01')`
- Behavior:
  1. Loads periodic roles (non-`Continuously`) with complete window fields.
  2. Computes `windowClosesAt()` for each role.
  3. If `windowClosesAt() <= now()`, updates `last_window_close_date` to the calculated close date.
  4. Leaves non-expired roles untouched.

## Factory Defaults

`OfficialRoleFactory` defaults:

- `window_plan` => `WindowPlan::Continuously`
- `open_window_duration` => `WindowDuration::SevenDays`
- `last_window_close_date` => `now()`

## Usage Guidelines

- Prefer `OfficialRole::query()` and relationships over manual joins.
- Assign users with pivot relation methods (`attach`, `sync`, `syncWithoutDetaching`).
- Keep role names human-readable; slug is generated automatically.
- When extending role scope logic, update model, factory, seeder, and tests together.
- For any question-facing role selection, prefer `scopeWithOpenWindow()` to avoid exposing closed windows.

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
