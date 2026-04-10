# Slofi — Personal Finance API

A REST API for personal finance management built with Laravel 13. Slofi lets you track accounts, credit cards, and transactions with automatic categorization via a rule-based tagging engine.

## About the Project

Slofi is the backend layer of a multi-platform personal finance system. It exposes a JSON API consumed by a React Native mobile app, a React + Inertia.js web frontend, and a Go + Bubble Tea terminal UI — all planned or in progress.

The architecture follows **Screaming Architecture** with an **Action pattern**: each domain use case lives in an isolated `Action` class, keeping controllers thin and the domain logic explicit and testable.

```
app/Domain/
├── Accounts/        — Checking, savings, cash, and investment accounts
├── CreditCards/     — Credit cards with billing cycle tracking
├── Transactions/    — Income, expenses, and transfers (polymorphic payable)
└── Tags/            — Tag taxonomy + rule-based auto-tagging engine
```

## Features

### Accounts
- Create and manage multiple accounts (checking, savings, cash, investment)
- Multi-currency support
- Per-account balance tracking

### Credit Cards
- Track credit cards with configurable cutoff and payment days
- **Billing cycle engine**: automatically computes the current billing period start/end and outstanding balance, with correct handling for short months (e.g. cutoff day 31 in February clamps to 28/29)

### Transactions
- Record income, expense, and transfer transactions
- Polymorphic payable — a transaction can belong to an account or a credit card
- **Atomic transfers**: debit + credit are created inside a database transaction, with bidirectional `transfer_pair_id` linking
- Filtering by date range, payable, type, and tag via query parameters

### Automatic Tagging
- Rule-based tagging engine: define rules on `description` or `merchant` fields using `contains`, `starts_with`, or `equals` operators (all case-insensitive)
- Rules are evaluated in descending priority order; a transaction can receive multiple tags
- Tags are assigned automatically on every transaction registration via a swappable `TaggingStrategyInterface`

### Auth
- Token-based authentication via **Laravel Sanctum**
- Scoped resource authorization — users can only access their own data

## Tech Stack

| Layer | Technology |
|---|---|
| Runtime | PHP 8.5 |
| Framework | Laravel 13 |
| Auth | Laravel Sanctum |
| Data objects | spatie/laravel-data |
| Query filtering | spatie/laravel-query-builder |
| Database | SQLite (testing) / MySQL or PostgreSQL (production) |

## Project Status

| Layer | Status |
|---|---|
| Laravel REST API | ✅ Complete |
| React Native (mobile) | 🔲 Planned |
| React + Inertia.js (web) | 🔲 Planned |
| Go + Bubble Tea (TUI) | 🔲 Planned |

### API Milestones

| Milestone | Tasks | Status |
|---|---|---|
| M0 — Scaffolding & config | 5 / 5 | ✅ |
| M1 — Migrations | 6 / 6 | ✅ |
| M2 — Authentication | 3 / 3 | ✅ |
| M3 — Accounts | 6 / 6 | ✅ |
| M4 — Credit Cards | 7 / 7 | ✅ |
| M5 — Tags & Rules | 11 / 11 | ✅ |
| M6 — Transactions | 6 / 6 | ✅ |
| M7 — Test Suite | 7 / 7 | ✅ |
| **Total** | **51 / 51** | ✅ |

**42 tests, 42 passing.**

## API Reference

All endpoints require a Bearer token (from `POST /api/login`) unless noted.

### Auth
| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/login` | Obtain an API token |
| POST | `/api/logout` | Revoke the current token |

### Accounts
| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/accounts` | List all accounts |
| POST | `/api/accounts` | Create an account |
| GET | `/api/accounts/{id}` | Get an account |
| PUT/PATCH | `/api/accounts/{id}` | Update an account |
| DELETE | `/api/accounts/{id}` | Delete an account |

### Credit Cards
| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/credit-cards` | List all credit cards |
| POST | `/api/credit-cards` | Create a credit card |
| GET | `/api/credit-cards/{id}` | Get a credit card |
| PUT/PATCH | `/api/credit-cards/{id}` | Update a credit card |
| DELETE | `/api/credit-cards/{id}` | Delete a credit card |
| GET | `/api/credit-cards/{id}/balance` | Current billing period and balance |

### Transactions
| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/transactions` | List transactions (filterable) |
| POST | `/api/transactions` | Register a transaction |
| GET | `/api/transactions/{id}` | Get a transaction |

**Query filters** (GET `/api/transactions`):
- `from` / `to` — date range (YYYY-MM-DD)
- `type` — `income`, `expense`, or `transfer`
- `payable_type` + `payable_id` — filter by account or credit card
- `tag_id` — filter by tag

### Tags
| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/tags` | List all tags |
| POST | `/api/tags` | Create a tag |
| GET | `/api/tags/{id}` | Get a tag |
| PUT/PATCH | `/api/tags/{id}` | Update a tag |
| DELETE | `/api/tags/{id}` | Delete a tag |
| GET | `/api/tags/{id}/rules` | List rules for a tag |
| POST | `/api/tags/{id}/rules` | Create a rule for a tag |
| GET | `/api/rules/{id}` | Get a rule |
| PUT/PATCH | `/api/rules/{id}` | Update a rule |
| DELETE | `/api/rules/{id}` | Delete a rule |

## Getting Started

```bash
# Install dependencies
composer install

# Copy environment file and configure your database
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Run the test suite
php artisan test
```

## License

MIT
