# TaxCore

TaxCore is a Laravel application for Indonesian payroll and PPh Pasal 21 calculations. It provides company workspaces, recipient records, payroll review and finalization, tax simulations, imports, worksheets, accounting journals, and Coretax exports.

The application uses a service layer for business operations. Its interface uses Blade, Tailwind CSS, Vite, and JavaScript.

## Features

- Six tax modules covering permanent employees, final-period reconciliation or resignation, periodic pensions, daily workers, non-employees, and final severance or pension benefits.
- Gross, Gross-Up, and Nett methods with integer-rupiah calculations; evidenced non-service contractor costs can be excluded from the recipient payment and taxable fee.
- Company and editable branch profiles with NPWP, NITKU, signatory details, and remembered active-company selection. Empty companies and accounts without transaction history can be deleted.
- Username and password login, company assignments, configurable role permissions, and audit records.
- Month and year filters, recipient search, spreadsheet editing (identity, period, BPJS, and income), pagination, and bulk payroll actions.
- Saved-record loading, calculation, and draft saving from the simulator; annual synchronization from finalized monthly history.
- Audited annual worksheet adjustments with reset to automatic calculations and worksheet/journal print selection.
- Marked demo data generation for 50 to 2,500 recipients, with previews and a scoped sample reset.
- Subject-specific XLSX templates, CSV, and pasted-data imports with previews, row validation, file/selected-period choices, and transactional append/replace modes. Replacement protects finalized periods and rejects stale previews.
- Payroll snapshots, tracked corrections, printable worksheets, and balanced accounting journals.
- XLSX and XML exports using BPMP, BP21, or BPA1 structures according to the tax module.

## Requirements

- PHP 8.3 or later within the supported PHP 8.x range.
- Composer 2.
- Node.js matching the installed Vite requirement, `^20.19.0 || >=22.12.0`.
- MySQL for the Laragon setup below.
- PHP extensions required by Laravel, plus PDO MySQL, SimpleXML, XMLWriter, and ZIP for database and spreadsheet operations.
- PDO SQLite and SQLite3 for the test suite.

Direct dependencies are defined in [composer.json](composer.json) and [package.json](package.json). Lockfiles pin the installed versions.

## Fresh installation

Run these commands from the project root. The examples use PowerShell.

```powershell
composer install
if (-not (Test-Path -LiteralPath '.env')) {
    Copy-Item -LiteralPath '.env.example' -Destination '.env'
}
php artisan key:generate --no-interaction
npm ci
```

Configure the application and database in `.env`. Replace the SQLite defaults with the MySQL connection for this setup, and supply your local database credentials.

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

Create the database using a MySQL client with permission to create databases.

```sql
CREATE DATABASE IF NOT EXISTS taxcore
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
```

Run the migrations, initialize default role permissions, and build the frontend.

```powershell
php artisan migrate --no-interaction
php artisan db:seed --no-interaction
npm run build
```

The default seeder initializes role permissions. Create the first administrator through the setup screen.

## Run locally

Start the application server.

```powershell
php artisan serve --host=127.0.0.1 --port=8000 --no-interaction
```

Keep a queue worker running in a second terminal for imports containing more than 200 rows.

```powershell
php artisan queue:work --sleep=2 --tries=1 --timeout=60 --no-interaction
```

For frontend development, run the Vite development server in another terminal.

```powershell
npm run dev
```

Open the setup screen and create a Super Admin using a username, email address, and password of at least 8 characters. The setup route closes after the first user exists. Subsequent accounts are managed through Manajemen Akses. [First administrator setup](http://localhost:8000/setup)

For Laragon virtual hosts, point the site's document root to this project's `public` directory and set `APP_URL` to the configured site URL.

## Payroll workflow

1. Create a company with its NPWP and signatory details. The application creates the head-office branch with code `000000`.
2. Add further branches and assign users to their companies.
3. Register recipients in Database Penerima, including their NIK or NPWP, PTKP status, and employment dates.
4. Select a company, branch, module, month, and year. Enter salary components manually or import a completed template.
5. Save payroll as Draft, review the period, and finalize it.
6. Export finalized payroll or open its worksheet to print or save as PDF through the browser.
7. To amend finalized payroll, open a correction with a reason. The application archives the previous revision and returns the period to Draft.

The payroll states progress from **Draft** to **Reviewed** to **Finalized**. Editing a reviewed entry returns its run to Draft.

Finalization stores the calculation results and company identity used for the period. Later company-profile changes preserve those finalized snapshots.

### Final-period reconciliation

Use the final-period module for December or a permanent employee's resignation month. An earlier final period must match the recipient's recorded end date.

The application accumulates earlier finalized payroll for the same recipient and company. Missing history requires opening balances and a recorded reason.

BPA1 export requires sufficient detail for its income components. When the opening gross balance consists entirely of salary, confirm that explicitly in the form. For mixed salary, allowances, bonus, THR, insurance, or tax benefits, import the earlier monthly detail before exporting BPA1.

Nett calculations protect the recipient's cash income before employee cash deductions. Employer-paid tax is included as a taxable benefit.

## Roles and access

The following permissions apply by default. Super Admin can change the permission matrix for the other roles.

| Role | Default capabilities |
| --- | --- |
| Super Admin | All permissions and all companies |
| Tax Manager | View, edit, import, export, review, finalize, and correct assigned-company payroll |
| Payroll Staff | View, edit, and import assigned-company payroll |
| Auditor | View and export assigned-company payroll |

User accounts carry company assignments. Imports require both payroll import and edit permissions. Passwords require at least 8 characters. The application protects the last active Super Admin from deactivation, deletion, or demotion. Accounts with import/review history must be deactivated instead of deleted.

## Imports

Download the TaxCore template from the selected module. It includes `Masa_Pajak`, `Tahun_Pajak`, recipient identity, tax method, and salary-component columns.

- Use XLSX, CSV, or pasted tabular data with the template headers.
- Enter dates as `YYYY-MM-DD` and monetary amounts as whole rupiah.
- Preserve NIK, NPWP, and NITKU as text so leading zeroes survive.
- XLSX imports read the first standard worksheet and require formulas to be replaced with values.
- The upload limit is 10 MB, with up to 10,000 data rows per import. XLSX contents are also limited to 30 MB after decompression.
- Review the preview and fix reported errors before confirming.
- Imports with up to 200 rows run synchronously. Larger imports are queued after confirmation.
- Select whether periods come from the file or the active month/year. Review the resulting periods in the preview.
- APPEND rejects duplicates. REPLACE archives and replaces draft/reviewed entries only for the selected company, branch, module, and periods in the preview. Changes after preview require a new preview.
- A failed commit rolls back replacement, employee, and payroll changes together.

Import states include Preview, Queued, Completed, and Failed. Use Perbarui status on the imports page to check queued work.

## Exports

Exports require finalized payroll for the selected branch, year, module, and month range.

- BPMP handles regular permanent-employee and periodic-pension withholding.
- BP21 handles daily-worker, non-employee, and final-benefit records. Each entry needs the recipient's NITKU, payment-document number, and document date.
- BPA1 handles final-period reconciliation.
- Export selections must have a consistent document family and withholding-company NPWP snapshot.
- Worksheets include calculation details and accounting journals. Browser printing supports saving them as PDF.

Bundled XML fixtures and TER reference data support automated format and calculation checks. Actual acceptance of exported files in Coretax remains a manual validation step. [DJP XML templates and converters](https://pajak.go.id/reformdjp/coretax/template-xml-dan-converter-excel-ke-xml)

## Service layer

Controllers coordinate requests, authorization, validation, and responses. The services own reusable business operations.

| Service | Responsibility |
| --- | --- |
| [TaxService](app/Services/TaxService.php) | Tax validation, TER lookup, progressive calculations, gross-up, and object-code selection |
| [PayrollService](app/Services/PayrollService.php) | Recipient changes, payroll calculations, history, review, finalization, corrections, and bulk actions |
| [PayrollImportService](app/Services/PayrollImportService.php) | Import templates, preview validation, and transactional batch commits |
| [SpreadsheetService](app/Services/SpreadsheetService.php) | XLSX generation, XLSX reading, and delimited-data parsing |
| [CoretaxExportService](app/Services/CoretaxExportService.php) | Finalized-record selection and document generation |
| [JournalService](app/Services/JournalService.php) | Balanced accounting entries |
| [AccessService](app/Services/AccessService.php) | Permission checks, company access, and audit recording |
| [CompanyService](app/Services/CompanyService.php) | Company and branch operations |
| [UserService](app/Services/UserService.php) | First-admin creation, account management, and role permissions |

## Project structure

```text
app/
  Http/Controllers/       HTTP entry points
  Http/Middleware/        Active-account checks
  Http/Requests/          Form validation and authorization
  Jobs/                   Queued import processing
  Models/                 Companies, recipients, payroll, access, and audit data
  Services/               Business operations
config/
  tax.php                 Rule version, supported years, PTKP, and TER bands
  taxcore.php             Tax modules, roles, and default permissions
database/
  factories/              Test and development data factories
  migrations/             Database schema
  seeders/                Default permissions and development seeders
resources/
  views/                  Blade pages and components
  css/                    Application styles
  js/                     Interface behavior
routes/web.php            Application routes
tests/
  Feature/                Calculation, workflow, security, import, and export checks
  Fixtures/coretax/        DJP sample XML and TER reference data
```

## Tests and checks

The test suite uses an in-memory SQLite database through [phpunit.xml](phpunit.xml).

```powershell
php vendor/bin/pest --compact
npm run build
php artisan view:cache --no-interaction
```

For a Laragon PHP installation where SQLite extensions are available but disabled, run Pest directly with the extensions enabled for that process.

```powershell
php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/pest --compact
```

Coverage includes TER thresholds, gross-up, refunds, final-period history, payroll snapshots, stale updates, company isolation, roles, import rollback, a 1,000-row import, and XML field structure.

Format PHP using the installed Pint binary.

```powershell
php vendor/bin/pint app database routes config/tax.php config/taxcore.php tests/Feature --format agent
```

## Troubleshooting

| Symptom | Action |
| --- | --- |
| Unknown database `taxcore` | Create the configured MySQL database and verify the credentials in `.env` |
| Missing sessions, cache, or jobs tables | Run the migrations before opening setup or starting workers |
| SQLite driver unavailable during tests | Enable PDO SQLite and SQLite3, or use the direct Pest command above |
| Vite manifest missing or frontend changes absent | Run `npm run build` or keep `npm run dev` running |
| Import remains Queued | Start the queue worker and check `php artisan queue:failed --no-interaction` |
| Export rejects the selected period | Check payroll finalization, document family, snapshot NPWP, and required document fields |
| Environment changes are not reflected | Run `php artisan config:clear --no-interaction` and restart the worker |

## Current scope

- Configured tax years are 2024 through 2026.
- Calculations cover standard resident-taxpayer scenarios without DTP facilities.
- Each recipient has one consolidated payment per company, module, and period. Separate payments within the same month require extending the payment model.
- Imports support the first standard XLSX worksheet, CSV, and pasted data. Legacy binary XLS and multi-sheet imports require additional support.
- Tests verify the implemented calculations and file structures. Browser UAT and successful submission to Coretax remain separate verification tasks.
