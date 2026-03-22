---
name: official-role
description: Official government role management in the NPAP platform. Activate when creating, editing, querying, seeding, or testing OfficialRole records; working with OfficialRole model relationships; managing official_role_user assignments; or attaching questions to official roles.
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
| Factory | `database/factories/OfficialRoleFactory.php` | Test data defaults for role creation |
| Seeder | `database/seeders/OfficialRoleSeeder.php` | Baseline list of national/local official roles |
| Migration | `database/migrations/2026_03_22_002550_create_official_roles_table.php` | Official roles table schema |
| Migration | `database/migrations/2026_03_22_002559_create_official_role_user_table.php` | User-role pivot schema |
| Migration | `database/migrations/2026_03_22_002608_add_official_role_id_to_questions_table.php` | Nullable role FK on questions |
| Tests | `tests/Feature/OfficialRoleTest.php` | Relationship, slug, and seeder behavior tests |

## Model Details

`OfficialRole` includes:

- Traits: `HasFactory`, `HasSlug`
- Fillable:
  - `name`
  - `slug`
  - `country_id`
  - `province_id`
  - `city_id`
- Slug behavior:
  - generated from `name`
  - saved to `slug`

Relationships:

- `users()` -> `BelongsToMany<User>`
- `country()` -> `BelongsTo<Country>`
- `province()` -> `BelongsTo<Province>`
- `city()` -> `BelongsTo<City>`
- `questions()` -> `HasMany<Question>`

## Schema Rules

### `official_roles`

- `id`
- `name`
- `slug` (unique)
- `country_id` (nullable FK)
- `province_id` (nullable FK)
- `city_id` (nullable FK)
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
2. Seeds baseline role names (11 roles).
3. Uses `updateOrCreate` to remain idempotent.
4. Stores roles with null province/city scope by default.

## Usage Guidelines

- Prefer `OfficialRole::query()` and relationships over manual joins.
- Assign users with pivot relation methods (`attach`, `sync`, `syncWithoutDetaching`).
- Keep role names human-readable; slug is generated automatically.
- When extending role scope logic, update model, factory, seeder, and tests together.

## Testing Conventions

For OfficialRole features, ensure tests cover:

- slug generation from name,
- nullable geographic scope behavior,
- user-role pivot assignment,
- question-to-role relationship,
- seeder baseline role count and key role presence.
