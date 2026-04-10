# Slofi — Personal Finance API

A REST API for personal finance management built with Laravel 13. Slofi lets you track accounts, credit cards, and transactions with automatic categorization via a rule-based tagging engine and an LLM-powered parser service.

## About the Project

Slofi is the backend layer of a multi-platform personal finance system. It exposes a JSON API consumed by a React Native mobile app, a React + Inertia.js web frontend, and a Go + Bubble Tea terminal UI — all planned or in progress.

The architecture follows **Screaming Architecture** with an **Action pattern**: each domain use case lives in an isolated `Action` class, keeping controllers thin and the domain logic explicit and testable.

```
app/Domain/
├── Accounts/          — Checking, savings, cash, and investment accounts
├── CreditCards/       — Credit cards with billing cycle tracking
├── Transactions/      — Income, expenses, and transfers (polymorphic payable)
├── Tags/              — Tag taxonomy + rule-based auto-tagging engine
├── Categories/        — Structured categorization (system + user-defined, i18n)
├── RecurringExpenses/ — Fixed recurring expenses and loans with end date tracking
└── Installments/      — Installment plans (compras a meses) with per-payment tracking
```

## Features

### Accounts
- Create and manage multiple accounts (checking, savings, cash, investment)
- Multi-currency support
- Per-account balance tracking

### Credit Cards
- Track credit cards with configurable cutoff day and payment grace days
- **Billing cycle engine**: automatically computes the current billing period start/end and outstanding balance, with correct handling for short months (e.g. cutoff day 31 in February clamps to 28/29)
- `payment_due_date` computed dynamically from cutoff date + grace days

### Transactions
- Record income, expense, and transfer transactions
- Polymorphic payable — a transaction can belong to an account or a credit card
- **Atomic transfers**: debit + credit are created inside a database transaction, with bidirectional `transfer_pair_id` linking
- Filtering by date range, payable, type, tag, and category via query parameters

### Automatic Tagging
- Rule-based tagging engine: define rules on `description` or `merchant` fields using `contains`, `starts_with`, or `equals` operators (all case-insensitive)
- Rules are evaluated in descending priority order; a transaction can receive multiple tags
- Tags are assigned automatically on every transaction registration via a swappable `TaggingStrategyInterface`

### Categories
- 21 system categories (expenses + income + `others`), translated via i18n — slugs stored in DB, names resolved by locale
- Users can create their own custom categories on top of the system ones
- One category per transaction — structured classification alongside free-form tags

### Recurring Expenses
- Define fixed recurring expenses (rent, subscriptions, gym) with configurable frequency: `daily`, `weekly`, `monthly`, or `yearly`
- A scheduled job (`ProcessScheduledTransactionsJob`) runs daily and auto-generates the corresponding transaction on each due date
- **Loan support**: set `ends_at` on a recurring expense to model a loan — the job stops generating transactions once the end date is reached

### Installment Plans
- Track purchases made in installments (compras a meses) linked to a credit card
- Records total installments, amount per installment, and a running count of paid installments
- A job generates the installment transaction on each billing cycle cutoff date and marks the plan as completed when all installments are paid

### Auth
- Token-based authentication via **Laravel Sanctum**
- Scoped resource authorization — users can only access their own data

## Tech Stack

### Backend API (this repo)

| Layer | Technology |
|---|---|
| Runtime | PHP 8.5 |
| Framework | Laravel 13 |
| Auth | Laravel Sanctum |
| Data objects | spatie/laravel-data |
| Query filtering | spatie/laravel-query-builder |
| Database | SQLite (testing) / MySQL (production) |

### Full System

| Layer | Technology | Status |
|---|---|---|
| Backend API | Laravel 13 (PHP 8.5) | ✅ Complete |
| Parser / AI Service | Python + FastAPI + Groq (llama-3.1-8b-instant) | 🔲 Next |
| Mobile | React Native | 🔲 Planned |
| Web | React + Inertia.js | 🔲 Planned |
| TUI | Go + Bubble Tea | 🔲 Planned |
| Deploy / CI/CD | GitHub Actions + Apache + DigitalOcean | 🔲 Planned |

## Project Status

### API Milestones

| Milestone | Status |
|---|---|
| M0 — Scaffolding & config | ✅ |
| M1 — Migrations | ✅ |
| M2 — Authentication | ✅ |
| M3 — Accounts | ✅ |
| M4 — Credit Cards | ✅ |
| M5 — Tags & Rules | ✅ |
| M6 — Transactions | ✅ |
| M7 — Test Suite | ✅ |
| M8 — Categories + i18n | ✅ |
| M9 — Recurring Expenses + Installment Plans | ✅ |
| M9.1 — Loan support (ends_at) | ✅ |

**70 tests, 70 passing.**

## API Reference

All endpoints require a Bearer token (from `POST /api/login`) unless noted.

### Auth
| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/register` | Register a new user |
| POST | `/api/login` | Obtain an API token |
| POST | `/api/logout` | Revoke the current token |
| GET | `/api/user` | Get the authenticated user profile |

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
- `category_id` — filter by category

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

### Categories
| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/categories` | List all categories (system + user's custom) |
| POST | `/api/categories` | Create a custom category |
| GET | `/api/categories/{id}` | Get a category |
| PUT/PATCH | `/api/categories/{id}` | Update a custom category |
| DELETE | `/api/categories/{id}` | Delete a custom category |

### Recurring Expenses
| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/recurring-expenses` | List all recurring expenses |
| POST | `/api/recurring-expenses` | Create a recurring expense |
| GET | `/api/recurring-expenses/{id}` | Get a recurring expense |
| PUT/PATCH | `/api/recurring-expenses/{id}` | Update a recurring expense |
| DELETE | `/api/recurring-expenses/{id}` | Delete a recurring expense |

### Installment Plans
| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/installment-plans` | List all installment plans |
| POST | `/api/installment-plans` | Create an installment plan |
| GET | `/api/installment-plans/{id}` | Get an installment plan |
| PUT/PATCH | `/api/installment-plans/{id}` | Update an installment plan |
| DELETE | `/api/installment-plans/{id}` | Delete an installment plan |

## Getting Started

```bash
# Install dependencies
composer install

# Copy environment file and configure your database
cp .env.example .env
php artisan key:generate

# Run migrations and seed system categories
php artisan migrate --seed

# Run the test suite
php artisan test
```

## License

MIT
