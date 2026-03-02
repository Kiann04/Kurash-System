# Kurash System — Summary

## Overview
Kurash System is a Laravel 12 + Inertia + Vue 3 web application for managing Kurash tournaments. It supports player membership, tournament registration, bracket generation, match management, public tournament views, and API endpoints for scoreboard/sync integrations.

## Tech Stack
- Backend: PHP 8.2, Laravel 12, Fortify (auth), Sanctum (API auth)
- Frontend: Vue 3, Inertia.js, Vite, Tailwind CSS
- Tooling: Pest (tests), Pint (lint), ESLint/Prettier

## Core Domains
- Players and membership
- Tournaments and registrations
- Age and weight categories
- Brackets and matches
- Match results and scoring

## Key Modules

### Models (Data Layer)
- Player: personal data, membership dates, auto-age, membership status
- Tournament: name/date/status
- TournamentRegistration: link between players and tournaments with categories
- AgeCategory, WeightCategory: eligibility and grouping
- Bracket: grouping by gender/age/weight, format (round robin or single elimination)
- TournamentMatch: match schedule, players, results, bronze match
- MatchResult: score and winner detail

Files: `app/Models/*.php`

### Admin Area
- Player management: CRUD, membership renewal, search/filter
- Tournament management: CRUD, registrations, documentation page
- Registration import: CSV/XLSX/DOCX parsing with matching logic
- Bracket management: generate brackets, auto-advance BYEs, bronze match sync, award resolution

Files: `app/Http/Controllers/Admin/*`, `app/Services/*`

### Public Area
- Home
- Tournament list/detail
- Brackets view
- Athletes list
- Rankings

Files: `app/Http/Controllers/Public/*`, `resources/js/pages/public/*`

### API (Scoreboard/Sync)
- Fetch tournaments with brackets and matches
- Fetch full tournament data
- Fetch scoreboard data ordered by global match order
- Update match metadata
- Update match results

Files: `app/Http/Controllers/Api/TournamentApiController.php`, `routes/api.php`

## Routing
- Web routes: public pages, admin pages, authentication, settings
- API routes: tournament sync and match updates

Files: `routes/web.php`, `routes/api.php`, `routes/settings.php`

## Bracket and Match Logic
- Brackets grouped by gender, age category, and weight category
- Round robin for <= 5 participants
- Single elimination for larger groups
- Auto-advance BYEs and heal bracket state on view
- Bronze match generation and sync for single elimination
- Global match ordering to maximize athlete rest time

Files: `app/Http/Controllers/Admin/BracketController.php`, `app/Services/MatchSchedulerService.php`, `app/Services/BronzeMatchService.php`

## Data Import
- Supports CSV/XLSX/DOCX
- Normalizes headers, resolves players by ID/email/name
- Resolves weight categories by ID, name, or by weight/gender/age

File: `app/Services/PlayerListImportService.php`

## Frontend Structure
- Pages organized by area: `resources/js/pages/admin/*`, `resources/js/pages/public/*`, `resources/js/pages/auth/*`, `resources/js/pages/settings/*`
- Shared UI components in `resources/js/components/*`

## Tests
- Mostly default auth and settings tests
- Minimal domain-specific tests

Files: `tests/Feature/*`, `tests/Unit/*`

## Notes / Observations
- There is both `TournamentMatch` (mapped to `matches` table) and a `Matches` model. This looks like legacy or unused duplication.
- Membership status auto-updates on player retrieval if expired.

---
Generated on 2026-03-02.
