# Medbook Queue Assessment

## 1. Plain-language Explanation and How the System works

Medbook is a mini Laravel and Angular application for managing a service-centre waiting queue.

Prerequisites: PHP 8.3+, Composer, MySQL 8+, Node 20+ and npm. I selected Angular not only because I have prior experience with Angular but also because the brief prefers it. I used a standalone component and no router because there is only one page.

The backend is the source of truth for queue calculation.
The system never stores a queue position. For every request it selects only `Waiting` customers, calculates each effective priority from the unchanged original priority and the elapsed minutes since the original arrival, then sorts by effective priority (Emergency, Priority, Normal), original arrival time, and finally the auto-incrementing ID as creation order. The first result is “serve next.” Threshold comparisons are inclusive. Status changes enforce the allowed transitions and serialize the one-customer-being-served rule in a database transaction.

## 2. Run the application

Terminal 1:

```bash
mysql -u root -p -e 'CREATE DATABASE medbook_queue'
cd backend
cp .env.example .env                 # set DB_USERNAME/DB_PASSWORD if needd\
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve --port=8001        # http://127.0.0.1:8001
```

Terminal 2:

```bash
cd frontend
npm install
ng serve         # http://localhost:4200
```

Open `http://localhost:4200/`. The backend defaults to SQLite; MySQL settings are described in `backend/README.md`.

### Verification

```bash
cd backend
php artisan test
```

If either server was already running, stop it with `Ctrl+C` before restarting. The Angular development server reads its proxy configuration only at startup. If the page says the API returned an unexpected response, confirm `http://127.0.0.1:8001/api/queue` returns JSON containing a `queue` property and make sure another application is not using port 8001.

The seed reproduces the supplied scenario using today's date. To assess it exactly at 11:15, replace YYYY-MM-DD with current day's date e.g in format (2026-08-25):
`GET http://127.0.0.1:8001/api/queue?at=2026-08-25T11:15:00%2B03:00`

Or run directly in terminal:

cURL (Bash / zsh): `curl "http://127.0.0.1:8001/api/queue?at=$(date +%Y-%m-%d)T11:15:00%2B03:00"`

PowerShell: `curl "http://127.0.0.1:8001/api/queue?at=$(Get-Date -Format 'yyyy-MM-dd')T11:15:00%2B03:00"`
Automated tests use an in-memory SQLite database for speed, while the application configuration and migrations target MySQL.

### N/B : Port 8001 is intentional: the Angular proxy in `frontend/proxy.conf.json` points there.

## 3. Decisions and edge cases Considered

- PHP enums express the only priorities and statuses; request classes reject missing, oversized, future, or unknown values with Laravel's structured `422` response.
- `QueueService` accepts the calculation time, keeping clock-dependent logic deterministic and independently testable. Effective priority and waiting time are returned only for Waiting records.
- Status transitions run in a database transaction. A singleton `queue_locks` row is locked before checking/updating active service, serialising simultaneous attempts even when no customer is currently Being Served.
- Exact 45/60/90-minute boundaries escalate; identical priority and arrival use ID creation order; completed/cancelled records are absent; returning to Waiting retains the original arrival clock; invalid/terminal transitions leave data unchanged.
- Completed and Cancelled customers are excluded. Original priority and arrival time do not change when a customer returns to Waiting. Invalid status changes and a second active customer produce clear 422 responses. The current frontend spec is replaced with real tests; the full business-rule coverage is in the backend.

## 4. How the solution is tested.

Run `cd backend && php artisan test` (ten tests, including the full supplied scenario and customer creation) and `cd frontend && npm run build`. API endpoints are `GET /api/queue`, `POST /api/customers`, and `PATCH /api/customers/{id}/status`.
The focused feature tests cover the supplied scenario, inclusive escalation boundaries, tie-breaking, queue exclusion, invalid transitions, clock preservation when returning to Waiting, one active customer, and customer creation.

## 5. Known Limitations

The interface is intentionally a single functional page. It uses invented seeded data only. This intentionally small solution has no authentication, pagination, audit trail, automatic UI refresh, or deployment configuration and Mobile development of this system is limited;

## 6. What I would improve for Production

For production I would add :

- authentication, authorization, audit logging, MySQL integration coverage, pagination, observability, and a database constraint or equivalent operational guard for the active-session invariant.
- Create a mobile version by first learn the target platform’s navigation, layout, accessibility, and testing conventions, then reuse the API and queue rules.
- row-level transaction lock. The current transaction-level singleton lock is designed to serialize concurrent status requests on the same database.

## 7. My Mobile Development Experience & Learning Approach

I Have Hands-on Experience: Practical experience in cross-platform and native mobile development using `React Native, Flutter, and Kotlin`. Worked across full feature lifecycles—building modular UI components, integrating REST APIs, handling offline state, and managing multi-language localization files (JSON).

### Approach to Learning New Frameworks/Platforms:

Architectural Mapping: Align mobile patterns (Jetpack Compose, Flutter widgets, or React Native components) with established web paradigms (Angular/React component lifecycle, reactivity, and unidirectional data flow).

Core Engineering First: Master state management, navigation routing, and platform-specific quirks (e.g., safe area insets, soft keyboard behavior, and background threading).

Iterative Proof-of-Concept: Fast-track mastery by building an end-to-end feature—connecting UI components to API integration, form handling, and local storage.

## 8.AI disclosure

GitHub Copilot was used as an assistant for codebase exploration to check if anything was missed during my initial implementation to verify completeness against guidelines, implementation assistance (e.g in terms of targeted arising bug fixes), and documentation drafting. The resulting code and documentation were intentionally reviewed before being accepted.
