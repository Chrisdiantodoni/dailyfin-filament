# AGENTS.md — Daily Finance (dailyfin-filament)

> This file is intended for AI coding agents. It describes the architecture, conventions, and critical business rules of this project. Assume the reader knows nothing about the codebase.

---

## Project Overview

**Daily Finance** (`dailyfin-filament`) is an internal web application for managing daily financial operations at car dealerships. It is built with **Laravel 12** and **Filament 4** (PHP 8.2+).

The application handles several daily financial workflows:
- **Setoran Brankas** (`CashierDeposit`) — Daily cashier deposits to the safe/bank.
- **Mutasi Kas** (`CashMutate`) — Daily cash mutation reports.
- **Validasi Setoran** (`ValidationDeposit`) — Finance validation of deposits.
- **Setoran Counter Service** (`CsServiceSparepart`, `CsUnit`) — Counter service deposits (service & spareparts, unit sales).
- **Penarikan Kas Pagi** (`cashier_takeout_money`) — Morning cash takeout reports.
- **Koordinator Audit** (`Coordinator`) — Audit coordinator oversight dashboard.

All financial data is scoped per **dealer** (`dealer_code`). Users can belong to one or multiple dealers.

**UI Language:** Indonesian (Bahasa Indonesia). All Filament labels, notification messages, PDF reports, and business terminology are in Indonesian. Code comments are mixed (Laravel defaults in English, custom logic comments often in Indonesian).

---

## Technology Stack

| Layer | Technology |
|-------|------------|
| Backend Framework | Laravel 12 (PHP ^8.2) |
| Admin Panel | Filament 4 |
| Frontend Build | Vite 7 + Tailwind CSS v4 |
| Database | SQLite (default), MySQL-compatible |
| Auth / RBAC | Spatie Laravel Permission (`spatie/laravel-permission`) |
| API Auth | Laravel Sanctum |
| Real-time | Laravel Reverb + Laravel Echo + Pusher JS |
| PDF Generation | `barryvdh/laravel-dompdf` |
| Excel Export | `maatwebsite/excel` |
| Image Processing | Intervention Image v3 |
| Password Policy | `yebor974/filament-renew-password` |
| Activity Log | `pxlrbt/filament-activity-log` |
| Testing | PHPUnit 11 |
| Code Style | Laravel Pint |

---

## Build and Development Commands

```bash
# Install PHP dependencies
composer install

# Install JS dependencies
npm install

# Run the full development stack (runs concurrently):
# - php artisan serve
# - php artisan queue:listen --tries=1
# - php artisan pail --timeout=0
# - npm run dev
composer dev

# Build frontend assets for production
npm run build

# Run tests
composer test
# or
php artisan test

# Code style fix
./vendor/bin/pint

# Filament asset publishing (after upgrades)
php artisan filament:assets
```

---

## Project Structure & Code Organization

### Filament v4 Architecture
Filament resources follow the **v4 directory convention** where `form()` and `table()` are extracted into dedicated classes:

```
app/Filament/Resources/{ResourceName}/
├── {ResourceName}Resource.php      # Entry point: model, nav, access control
├── Pages/
│   ├── List{ResourceName}.php
│   ├── Create{ResourceName}.php
│   └── Edit{ResourceName}.php
├── Schemas/
│   └── {ResourceName}Form.php      # Form schema definition
└── Tables/
    └── {ResourceName}Table.php     # Table columns, filters, actions
```

**Resources:**
- `CashierDeposits` — Setoran Harian ke Brankas
- `CashMutates` — Mutasi Kas
- `CounterServiceDeposits` / `CounterServiceUnits` — Setoran Counter Service
- `Dealers` — Dealer master data
- `Neqs` — NEQ data
- `TakeoutMoney` — Penarikan Kas
- `Users` — User management
- `ValidationDeposits` — Validasi Setoran

### Custom Pages & Widgets
- `app/Filament/Pages/Dashboard.php` — Custom dashboard with date/dealer filters.
- `app/Filament/Pages/*Detail.php` — Custom detail pages for various reports.
- `app/Filament/Widgets/` — Dashboard stat widgets (cached for 3 minutes).
- `app/Filament/Widgets/Concerns/HasDashboardFilters.php` — Shared trait for applying date range and dealer filters to widgets.

### Models & Observers
- All domain models use **UUIDs** as primary keys (`HasUuids` trait).
- `Dealer` uses `dealer_code` (string) as its primary key.
- Models use `protected $guarded = []` rather than `$fillable`.
- Observers are registered via the `#[ObservedBy([...])]` PHP attribute on models.
- `app/Observers/` handle sending **database + broadcast notifications** on create/update events.

### Helpers
`app/Helpers/` contains globally autoloaded helper functions:
- `formatNumber($number)` — Formats as Indonesian Rupiah: `Rp.xxx.xxx`
- `formatDate($date)` — Formats as `d M Y`
- `can($permission)`, `cannot($permission)`, `canAny([...])`, `cannotAny([...])` — Spatie permission wrappers
- `isCoordinator()` — Checks if user has "Coordinator Resources" permission
- `isLateValidateDeposit($created_at, $datePublished)` — Checks if a validation deposit is late (deadline = H+1 at 12:00)
- `getRole()` — Returns the user's first role name

### Services
- `app/Services/ImageCompressionService.php` — Resizes, converts to WebP/JPG, and generates unique filenames for uploaded images. Uses `imagick` if available, otherwise `gd`.

### Support
- `app/Support/UserDealerContext.php` — Utility class to get the current user's dealer associations, check single vs. multiple dealers, and retrieve the first dealer name/code.

### Notifications
- `app/Notifications/NotificationSent.php` — Sends notifications via **database + broadcast** (Laravel Reverb). Used by observers to notify relevant users when records are created or updated.

### PDF & Excel Exports
- `app/Http/Controllers/ExportPdfControler.php` — Generates PDF reports using DomPDF. Views are in `resources/views/pdf/`.
- `app/Exports/` — Maatwebsite Excel export classes for each report type.

### Middleware
- `PasswordResetChecker` — Forces password reset if `password_reset_at` is null.
- `PreventCashierDeposit` — Prevents duplicate cashier deposits for the same dealer on the same day.
- `PreventTakeoutMoney` — Similar duplicate prevention for takeout money.

---

## Database & Migrations

- Default connection: **SQLite** (see `.env.example`).
- Migrations are in `database/migrations/`.
- A recent migration (`2026_05_12_090000_add_performance_indexes_for_daily_finance_tables.php`) adds performance indexes.
- Factories exist for `User` but seeders are minimal (`DatabaseSeeder.php` only seeds a test user).

### Key Relationships
- `users` ↔ `dealers` via `dealer_users` (many-to-many pivot).
- All financial records (`cashier_deposits`, `cash_mutates`, `validation_deposits`, etc.) belong to a `dealer` via `dealer_code` and to a `user` via `user_id`.

---

## Role-Based Access Control (RBAC)

Roles (defined in `app/Enums/Role.php`):
- `IT` — Administrator
- `Cashier` — Daily data entry
- `Finance Operation` (`Finance Operation`) — First-level approval
- `Finance Spv` (`Finance Spv`) — Second-level approval
- `Finance Manager` — Management oversight
- `Audit Coordinator` (`Audit Coordinator`) — Audit oversight
- `Counter Service` — Counter service data entry

**Access Patterns:**
- Filament resources implement `canAccess()` using `canAny([...])` to check multiple permissions.
- Navigation badges show pending items for `Finance Operation` users.
- Data is always scoped to the user's assigned dealers via `Auth::user()->dealer_users()->pluck('dealer_code')`.

---

## Business Rules & Critical Logic

### Late Submission Deadlines
1. **Cashier Deposit (`CashierDeposit`)**: Submissions after **19:00** are flagged as "Late". Late submissions trigger additional notifications to **Audit Coordinator** users.
2. **Validation Deposit (`ValidationDeposit`)**: Submissions after **H+1 12:00** (noon the next day) are considered late. Logic is in `app/Helpers/FormatHelpers.php` (`isLateValidateDeposit`).

### Approval Workflow
- Most financial records have a `status` field with values: `request`, `approve`, `reject`.
- When a record is revised (returns to `request`), notifications are resent to Finance Ops.
- Observers handle notification dispatch automatically on model events.

### Balance Calculation (Cashier Deposit)
- `start_balance` is fetched from the **latest approved** record for the selected dealer.
- `end_balance = start_balance + today_income - expense - bank_deposit`
- `total_deposit = end_balance - invoice_nominal`
- These are calculated in real-time in the form using `live(onBlur: true)` and `RawJs` masks for Indonesian number formatting.

### Image Uploads
- Images are uploaded to the `public` disk.
- The `ImageCompressionService` resizes images to 900px width and can convert them to WebP.
- Multiple images are supported on several forms (e.g., `cashier_images`, `cash_images`).

---

## Testing Strategy

- **Test Framework:** PHPUnit 11
- **Configuration:** `phpunit.xml`
- **Test Database:** SQLite in-memory (`:memory:`)
- **Test Suites:** `Unit` (`tests/Unit/`) and `Feature` (`tests/Feature/`)
- **Current State:** Minimal test coverage. Only example tests exist. The project would benefit from Feature tests for the Filament resources and approval workflows.

Run tests with:
```bash
composer test
```

---

## Security Considerations

1. **HTTPS Enforcement:** `AppServiceProvider` forces `https://` scheme when `APP_URL` starts with `https://`.
2. **Password Policy:** Users with `password_reset_at === null` are forced to reset their password via `PasswordResetChecker` middleware.
3. **RBAC:** Every Filament resource checks permissions via `canAccess()`. Do not expose resources without explicit permission checks.
4. **Data Scoping:** All Eloquent queries in resources use `whereIn('dealer_code', ...)` scoped to the authenticated user's dealers.
5. **CSRF & Auth:** Standard Laravel CSRF protection and session-based auth (via Filament).
6. **Image Handling:** Uploaded images are stored on the `public` disk. Ensure the `public/storage` symlink is correct in production.

---

## Deployment Notes

- **Environment:** Copy `.env.example` to `.env` and generate keys (`php artisan key:generate`).
- **Assets:** Run `npm run build` before deploying. The `public/build/` directory contains the Vite manifest.
- **Queue:** The `composer dev` script runs `queue:listen`. In production, use a proper queue worker (e.g., Supervisor) with `php artisan queue:work`.
- **Broadcasting:** Laravel Reverb is installed for real-time notifications. Ensure the Reverb server is running in production if real-time features are required.
- **Filament Assets:** After any Filament upgrade, run `php artisan filament:assets`.
- **Timezone:** Set to `Asia/Jakarta` in `AppServiceProvider` via `FilamentTimezone::set('Asia/Jakarta')`.

---

## Conventions for Agents

1. **Language:** Keep all Filament labels, notification text, and user-facing strings in **Indonesian**.
2. **Money Formatting:** Always use `formatNumber()` or the `@formatNumber` Blade directive for currency. Store raw integers in the database.
3. **Number Inputs:** Use `RawJs::make('$money($input, \',\', \'.\')')` for Indonesian thousands separators in Filament forms.
4. **Dealer Scoping:** When querying financial models, always scope by the user's dealer codes:
   ```php
   $dealerCodes = Auth::user()->dealer_users()->pluck('dealer_code')->all();
   $query->whereIn('dealer_code', $dealerCodes);
   ```
5. **Permissions:** Use `can()`, `cannot()`, `canAny()`, `cannotAny()` helpers for permission checks. Never rely solely on role names.
6. **UUIDs:** Use `Str::uuid()` or `$model->id` (auto-generated) when creating related records. Do not assume auto-incrementing integers.
7. **Observers:** If adding new models that need notifications, create an observer in `app/Observers/` and attach it with `#[ObservedBy([...])]`.
8. **Dashboard Widgets:** When adding new dashboard widgets, use the `HasDashboardFilters` concern and implement `getCachedData()` with a reasonable cache TTL (default is 3 minutes).
9. **Forms & Tables:** Follow the existing Filament v4 pattern — extract form logic into `Schemas/{Resource}Form.php` and table logic into `Tables/{Resource}Table.php`.
10. **Images:** Use `ImageCompressionService` for any new image upload features to maintain consistent sizing and format.
