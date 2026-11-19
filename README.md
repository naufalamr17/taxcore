# TaxCore

TaxCore is a Laravel payroll and PPh Pasal 21 application. Its main workspace follows [the supplied HTML reference](taxcore_pph_21_enterprise_sistem_payroll_kalkulator_pajak_pp_58_2023%20%2815%29%201.html), including the sidebar, tables, filters, forms, and modal dialogs. Laravel retains authentication, company access, database storage, user management, and audit logging.

## Features

- Six modules: monthly permanent employees, December/resignation (1721-A1), periodic pensions, daily workers, non-employees, and final severance/pension benefits.
- Gross, Gross-Up, and Nett methods using the reference interface's calculation engine.
- Monthly/annual payroll views, years 2020–2035, search, pagination, and spreadsheet autosave.
- Combined company/TKU profiles with NPWP, 22-digit NITKU, address, and independent branch signatories.
- Reference-style dialogs for payroll input, TER tables, imports, bulk generation, worksheets, and accounting journals.
- Module-specific templates, Excel/CSV uploads, pasted tables, import previews, and append/replace modes. Replacement preserves other modules and periods.
- Generators for 100, 500, or 1,000 example payroll rows and a sample reset that preserves manual records.
- Editable annual worksheets and worksheet/journal printing.
- XLSX and XML exports matching the HTML reference, with export actions recorded in the audit log.
- Configurable role permissions, company assignments, and retained Laravel user management.

## Requirements

- PHP 8.3 and Composer 2.
- Node.js compatible with Vite: `^20.19.0 || >=22.12.0`.
- MySQL/MariaDB for the Laragon setup below.
- Laravel's PHP extensions, PDO MySQL, SimpleXML, XMLWriter, and ZIP.
- PDO SQLite and SQLite3 for the test suite.

Package versions are pinned in `composer.lock` and `package-lock.json`.

## Local installation

Run from the project root. These examples use PowerShell.

```powershell
composer install
if (-not (Test-Path -LiteralPath '.env')) {
    Copy-Item -LiteralPath '.env.example' -Destination '.env'
}
php artisan key:generate --no-interaction
npm ci
```

Configure `.env` with your local database credentials:

```dotenv
APP_NAME=TaxCore
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=taxcore
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Create the `taxcore` database, then run:

```powershell
php artisan migrate --no-interaction
php artisan db:seed --no-interaction
npm run build
php artisan serve --host=127.0.0.1 --port=8000 --no-interaction
```

Open `/login` on the configured application URL. For Laragon virtual hosts, point the document root to `public` and set `APP_URL` accordingly.

During frontend development, run `npm run dev` in another terminal. Rebuild with `npm run build` after frontend changes when the development server is not running.

## HTML demo seeder

`DatabaseSeeder` initializes default role permissions and calls [HtmlDemoSeeder](database/seeders/HtmlDemoSeeder.php). Demo data is created only when `APP_ENV` is `local` or `testing`.

For a fresh local database, the demo contains:

- **1 company:** PT Nusantara Teknologi Mandiri, NPWP `0127543973027000`.
- **2 TKUs:** head office in Jakarta and the Surabaya branch.
- **6 recipients:** Budi Santoso, Rina Wijaya, Dimas Prakoso, Siti Nurhaliza, Hendra Wijaya, and Nadia Kusuma.
- **8 payroll rows:** six in November 2026 and two annual rows in December 2026, with identity, PTKP, tax method, salary, allowances, bonus, THR, BPJS, and pension components taken from the HTML reference.
- **4 active accounts** with the roles listed below.

| TKU | Code | NITKU | Signatory |
| --- | --- | --- | --- |
| Kantor Pusat, Jakarta | `000000` | `0127543973027000000000` | Ir. Hendra Wijaya, M.B.A. |
| Cabang Surabaya | `000001` | `0127543973027000000001` | Budi Santoso, S.E. |

Payroll examples belong to the head-office TKU. The Surabaya TKU starts without payroll rows so branch isolation remains intact. Select **November 2026** to see the six initial rows, or **December 2026** for the two annual examples.

### Demo login accounts

These credentials are for local development and testing only. Passwords are hashed in the database.

| Username | Password | Name | Role |
| --- | --- | --- | --- |
| `admin` | `admin123` | Super Administrator | `SUPER_ADMIN` |
| `taxmanager` | `pajak123` | Budi Santoso, S.E. | `TAX_MANAGER` |
| `payroll` | `gaji1234` | Siti Nurhaliza, S.Psi. | `PAYROLL_STAFF` |
| `auditor` | `audit123` | Rina Wijaya, S.Ak. | `AUDITOR` |

Emails use `<username>@taxcore.test`. Newly created demo accounts are assigned to the demo company. Super Admin can access every company; the other accounts follow their company assignments and role permissions.

The HTML's `gaji123` password has seven characters. The seeded Payroll Staff account uses **`gaji1234`** to meet the application's eight-character minimum.

### Running the seeder again

```powershell
php artisan db:seed --no-interaction
```

To run only the demo dataset:

```powershell
php artisan db:seed --class=HtmlDemoSeeder --no-interaction
```

Seeding adds missing demo records without resetting existing accounts, passwords, roles, account status, company assignments, profiles, employee identities, payroll edits, or permission overrides. Existing usernames keep their current credentials; the password table applies only to newly created accounts. Existing recipients with a matching NIK are skipped rather than having their identity or payroll overwritten.

The operation is transactional. If an existing `admin` account is inactive or has another role and no active Super Admin exists, the demo seeder stops and rolls back instead of granting privileges or reactivating that account.

### Installation without demo data

Seed only role permissions:

```powershell
php artisan migrate --no-interaction
php artisan db:seed --class=RolePermissionSeeder --no-interaction
```

Open `/setup` to create the first Super Admin. Setup closes after the first account exists. Later accounts are managed through **Manajemen Akses**. In production, the default seeder initializes role permissions but skips demo accounts and payroll.

## Workspace workflow

1. Log in and choose the company/TKU in the payroll filter.
2. Select the month and year, then use **Tambah Pegawai** or import a module template.
3. Edit through the same modal or enable **Mode Spreadsheet** for automatic saving.
4. Use the simulator navigation to work with the other tax modules.
5. Open **Kertas Kerja** for calculation details, annual adjustments, accounting journals, and printing. The annual module can pull monthly data.
6. Export Excel or XML from the workspace. Use **Log Audit** and **Manajemen Akses** for activity history and account administration.

The main HTML workspace does not require the older Draft/Reviewed/Finalized workflow. Legacy server services remain available internally; the main interface uses `HtmlWorkspaceService` and the reference JavaScript engine.

## Roles and access

| Role | Workspace access by default |
| --- | --- |
| Super Admin | All permissions and companies; user management and company audit |
| Tax Manager | View, edit, import, export, and worksheet corrections for assigned companies |
| Payroll Staff | View, edit, and import for assigned companies |
| Auditor | View and export for assigned companies |

Super Admin can configure the permission matrix. The application protects the last active Super Admin and checks company/branch access on server requests. Stale payroll edits are rejected using record versions.

## Export scope

Workspace exports follow the prototype: XLSX uses a `Coretax_Import` sheet and XML uses an `eBupot21Coretax` root. These are the HTML reference's formats. Successful acceptance by DJP/Coretax still requires manual upload validation.

## Key files

| Path | Purpose |
| --- | --- |
| `resources/views/workspace.blade.php` | Reference workspace markup and dialogs |
| `resources/js/html-workspace.js` | Reference calculations and Laravel persistence integration |
| `resources/css/html-workspace.css` | Reference styles and Tailwind utilities |
| `app/Http/Controllers/HtmlWorkspaceController.php` | Workspace, persistence, and export-audit endpoints |
| `app/Services/HtmlWorkspaceService.php` | Database mapping, version checks, and company isolation |
| `database/seeders/HtmlDemoSeeder.php` | Reference company, TKU, payroll, and login accounts |
| `database/seeders/RolePermissionSeeder.php` | Default role permissions |

## Tests and checks

```powershell
php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/pest --compact
npm run test:ui
npm run build
php artisan view:cache --no-interaction
php vendor/bin/pint --dirty --format agent
```

The PHP suite uses an isolated SQLite test database. Seeder coverage includes reference data, account hashing and login, repeat runs, existing-data preservation, transaction rollback, and the production guard. Frontend tests compare calculation results with the supplied HTML and exercise persistence, dialogs, imports, exports, spreadsheet editing, branch isolation, user management, audit, and bulk data in native Chrome when Chrome is installed. Browser tests use a temporary database and profile.

## Troubleshooting

| Symptom | Action |
| --- | --- |
| Unknown database `taxcore` | Create the database and check `.env` |
| Missing tables or TKU profile column | Run `php artisan migrate --no-interaction` |
| Demo rows are not visible | Select the demo company, head-office TKU, and November/December 2026 |
| Demo password does not work for an existing username | Use that account's current password; seeding does not reset accounts |
| Demo data is not created | Check that `APP_ENV` is `local` or `testing` |
| SQLite driver unavailable during tests | Use the direct Pest command with SQLite extensions above |
| Frontend changes do not appear | Run `npm run build` or keep `npm run dev` running |
| Environment changes are not reflected | Run `php artisan config:clear --no-interaction` |
