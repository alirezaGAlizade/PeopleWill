# PeopleWill

PeopleWill is a National Participation and Accountability Platform (NPAP) that operationalizes structured public participation and official accountability workflows. It enables citizens to submit civic questions, gather support, trigger mandatory official responses, run two-criteria evaluation voting, execute remediation periods, and apply formal outcomes such as case closure, disqualification, or recall (where legally applicable).

## Vision and Legal Basis

This project is built around the draft law specification in `draft_law_national_participation_en_v1.pdf` ("Foundational Law of the National Participation System and Digital Accountability", v1.0).

Core legal product principles include:
- Time-windowed question portals for national officials and continuously open portals for many local officials
- Threshold-based escalation from question to mandatory official response
- Two independent evaluation criteria: transparency/truthfulness and action/progress
- Remediation + second evaluation vote for rejected responses
- Three-level outcomes: closure, disqualification, and recall/removal rules

## Covered Officials (First-Page Laws)

| Official / Institution | Voter Scope | Window / Portal | Mandatory Response Threshold | Outcome Model |
| --- | --- | --- | --- | --- |
| President | Nationwide | Every 6 months, 7 days | 3% | Disqualification and recall model |
| First Vice President | Nationwide | Every 4 months, 7 days | 5% | Managerial review by President |
| Minister | Nationwide | Every 3 months, 7-10 days | 4% | Mandatory executive action |
| Head of key independent national institution | Nationwide | Every 4 months, 7 days | 4% | Oversight/appointment board action |
| Member of Parliament | Electoral district | Every 2 months, 7 days | 6% | Disqualification and recall model |
| Governor (elected model) | Province | Continuously open | 5% | Disqualification and recall model |
| Governor (appointed model) | Province | Continuously open | 5% | Mandatory managerial action |
| Provincial Council | Province | Continuously open | 5% | Disqualification and recall model |
| Mayor | City | Continuously open | 4% | Disqualification and recall model |
| City Council | City | Continuously open | 5% | Disqualification and recall model |
| County Governor | County | Continuously open | 6% | Managerial action (appointed), recall if elected model is later defined |

## Data Integrity and Auditability

PeopleWill is designed for high-trust public auditability:
- Append-only event ledger with timestamped hash chaining for critical events
- Multi-institutional cryptographic anchoring of ledger state
- Verifiable vote receipts (counted-as-cast) without revealing vote choice
- Public bulletin board of counted ballot hashes (not ballot contents)
- Re-cast support until voting deadline; only last valid ballot counts
- Multi-signature controls for sensitive governance actions
- WORM backups and tamper-evident access auditing

## Tech Stack

Backend:
- PHP 8.5
- Laravel 13
- Laravel Fortify (headless auth)
- Pest 4 (tests)
- Pint (formatting)

Frontend:
- React 19
- Inertia.js v2
- Tailwind CSS v4
- TypeScript 5

Tooling:
- Laravel Sail (Docker workflow)
- Laravel Wayfinder (typed route generation for TS)
- Vite 8
- ESLint 9
- Prettier 3

## Architecture

The project follows DDD-oriented boundaries and layered design:

Bounded contexts:
- Identity and Eligibility
- Jurisdictions
- Civic Cases
- Voting
- Audit and Integrity
- Governance Ops

Layering:
- Domain
- Application
- Infrastructure
- Interface (HTTP/UI/workers)

## Implemented Features (Current State)

- Question management with effective scope and ownership controls: [`app/Models/Question.php`](app/Models/Question.php), [`app/Http/Controllers/QuestionController.php`](app/Http/Controllers/QuestionController.php)
- Polymorphic voting subsystem with toggle behavior and per-user state: [`app/Models/Vote.php`](app/Models/Vote.php), [`app/Models/Concerns/HasVotes.php`](app/Models/Concerns/HasVotes.php)
- Multi-language translations with EN/FA and RTL support: [`lang/en/app.php`](lang/en/app.php), [`lang/fa/app.php`](lang/fa/app.php), [`resources/js/hooks/use-translations.ts`](resources/js/hooks/use-translations.ts)
- Laravel Fortify authentication and security pages
- Geographic hierarchy and targeting (country/province/city)

## Getting Started

### Prerequisites
- Docker
- Docker Compose

### Setup
1. Clone the repository.
2. Copy environment file:
   ```bash
   cp .env.example .env
   ```
3. Install PHP dependencies (if Sail is not yet bootstrapped locally):
   ```bash
   docker run --rm -v "$(pwd)":/app composer install --ignore-platform-reqs
   ```
4. Start Sail:
   ```bash
   vendor/bin/sail up -d
   ```
5. Generate app key:
   ```bash
   vendor/bin/sail artisan key:generate
   ```
6. Run migrations and seeders:
   ```bash
   vendor/bin/sail artisan migrate --seed
   ```
7. Install frontend deps and build assets:
   ```bash
   vendor/bin/sail yarn install
   vendor/bin/sail yarn run build
   ```
8. Open the app in your browser (default `http://localhost` unless overridden in `.env`).

## Development Commands

```bash
# Run tests
vendor/bin/sail artisan test --compact

# Format PHP changes
vendor/bin/sail bin pint --dirty --format agent

# Run frontend dev server
vendor/bin/sail yarn run dev

# Inspect routes
vendor/bin/sail artisan route:list
```

## Project Structure (Abbreviated)

```text
app/            # Domain + HTTP + policies + models
resources/js/   # Inertia React pages, components, hooks, routes
database/       # Migrations, factories, seeders
routes/         # Web and settings routes
tests/          # Pest feature/unit tests
lang/           # Translation files (en/fa)
```

## Contributing

- Keep the platform publicly auditable and aligned with legal-accountability goals.
- Every behavioral change should be covered by tests.
- Run formatting and relevant checks before opening a PR.

## License

MIT

