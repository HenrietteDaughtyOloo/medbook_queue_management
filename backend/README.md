# Medbook Queue Backend 

Laravel REST API for the Medbook service-centre queue.
While summarized in the root README.md, this section provides a modular, deep-dive breakdown of backend and rules
## How the queue works

Only customers with `Waiting` status are included. The API calculates waiting time from the original `arrival_at` timestamp to the requested assessment time (or the server clock). It derives effective priority without changing the stored original priority:

- `Emergency` always remains `Emergency`.
- `Priority` escalates to `Emergency` after 45 minutes, inclusive.
- `Normal` escalates to `Priority` after 60 minutes and `Emergency` after 90 minutes, both inclusive.

Customers are ordered by effective priority, original arrival time, then database ID as the creation-order tie-break. Queue positions are assigned from that calculated collection. The queue endpoint accepts an optional `at` parameter, which makes threshold and scenario checks deterministic.

## Setup

Requirements: PHP 8.3+, Composer, and SQLite or MySQL.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve --host=127.0.0.1 --port=8001
```

The default configuration uses SQLite. To use MySQL, set `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in `.env` before running the migrations.

The seeder creates the supplied five-customer scenario using current day's date. The deterministic version of that scenario is covered by the feature tests.

## API

| Method | Endpoint | Purpose |
| --- | --- | --- |
| `GET` | `/api/queue?at=2026-08-20T11:15:00Z` | Return calculated queue, positions, next customer, and active customer |
| `POST` | `/api/customers` | Add a Waiting customer |
| `PATCH` | `/api/customers/{customer}/status` | Apply a valid status transition |

Customer creation validates required fields, supported priorities, and an arrival time no later than the current time. Invalid transitions and attempts to serve a second customer return HTTP 422 validation errors with a clear `status` message.

## Tests

```bash
php artisan test
```

`tests/Feature/QueueRulesTest.php` covers the supplied scenario, inclusive escalation thresholds, creation-order ties, exclusion of non-Waiting customers, invalid transitions, returning to Waiting without resetting the clock, one active customer, and ISO timestamp creation.

## Decisions and edge cases

- Queue calculation accepts a point in time instead of hiding the clock inside the ordering logic.
- Original priority and original arrival time are never overwritten.
- Completed and Cancelled customers remain stored but are excluded from the queue.
- Status transitions are defined centrally in `CustomerStatus`.
- A singleton database lock row is locked inside the status transaction before checking and changing `Being Served`, protecting the one-active-customer rule when concurrent requests share the database.

For production, I would also add database-level monitoring and integration tests against the chosen MySQL version, and review transaction behavior under the deployment’s isolation level.
