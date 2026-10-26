 # IT Asset Manager

 IT Asset Manager is a PHP/MySQL application for managing IT assets, supplies,
 employee and department management, accountability, keycards, maintenance,
 deployments, software licenses, and audit history.

This repository contains application source and the install schema. It does
not contain production data or required user-uploaded files. Keep the GitHub
repository private unless deployment-specific credentials, defaults, content,
and ownership have been reviewed for public release.

## Requirements

- PHP 8.0 or later (PHP 8.5 is used in CI)
- PHP extensions: PDO MySQL, OpenSSL, and Fileinfo
- MySQL 8.x (or compatible MariaDB)

## Quick Start (Local)

1. Install XAMPP with PHP 8.0+ and MySQL/MariaDB, then start Apache and MySQL.
   Keep Apache's `mod_rewrite` enabled and `AllowOverride` set to `All` for this
   project so the existing `*.php` application URLs continue to route through
   the front controller.

2. Open phpMyAdmin, create the `itims_db` database, select it, and import
   `database/schema.sql`.

3. Configure local database credentials:

   - Copy `config/db.local.example.php` to `config/db.local.php`.
   - Update the local file with your database credentials and replace the
     license-key encryption placeholder with 32 random bytes encoded as Base64.
     Generate a value with `php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"`.
     This file is ignored by Git; do not commit it.
   - For production, use a dedicated MySQL user with least privilege (not
     `root`).

4. Run the database migration once (also required when upgrading an existing database):

   ```bash
   php install/database_migrate.php
   ```

   This ensures required supporting tables exist, including linked employee
   recipients for shared accountability records, adds separate Keycards page
   permissions, creates the department directory from existing employee
   records, normalizes legacy `Admin` / `Tech` roles to `admin` / `staff` /
   `viewer`, and prepares keycard employee links for multi-employee assignments.

5. With Apache and MySQL running, open `http://localhost/itims/install/setup.php`
   and create the first administrator using a unique password of at least 12
   characters. The application no longer seeds a shared default administrator.

6. Run the app:

   ```bash
   php -S localhost:8000 -t . router.php
   ```

   Run this command from the project directory. With a standard XAMPP install,
   the PHP executable is `C:\xampp\php\php.exe`.

7. Open:

   - http://localhost:8000/

The dashboard and application pages are rendered by PHP and use MySQL for
data storage. No Node.js build step is required to run or update the dashboard.

## Production Security

- Re-run `php install/database_migrate.php` after upgrading so the persistent
  login rate-limit table is created.
- Serve the application only over HTTPS in production. Session cookies are
  marked secure when HTTPS is detected.
- Create a unique administrator password during first-run setup, and grant the
  database account only the permissions the application requires.
- Keep `config/db.local.php` out of the web root's public access; Apache is
  configured to deny direct requests to the `config` directory.
- Never commit local uploads, database dumps, credentials, or production
  employee/asset records. The repository `.gitignore` excludes local
  configuration and uploaded content.

## Project Structure

- `index.php`: public front controller; routes legacy page URLs through an allowlist
- `app/bootstrap.php`: route definitions and application dispatch
- `app/pages/`: PHP application pages and request handlers
- `includes/`: shared layout, authentication, dashboard view, and security helpers
- `config/`: database configuration; local credentials stay untracked
- `database/schema.sql`: initial database schema
- `database/legacy/`: retained one-off SQL scripts for older installations
- `install/`: first-run setup and database migrations
- `tests/`: automated PHP regression tests
- `assets/`: browser styles, images, and other static files
- `uploads/`: user uploads (asset photos, scanned files, etc.)

Apache and the built-in PHP development server preserve the existing page
URLs. Application source, configuration, database scripts, and tests are
blocked from direct web access.

The file explorer stores managed files under `uploads/file_manager/`, blocks
direct web access to that directory, and serves files only through the
authenticated application. Run `php install/database_migrate.php` to create
its metadata table and storage directory. Images open in a zoomable, rotatable
preview modal; PDFs and plain text/CSV files preview in-app, while other
formats are offered as authenticated downloads.

Software license attachments are stored behind an access-denied Apache rule
and served through the authenticated Software Licenses page. When using PHP's
built-in development server, start it with `router.php` as shown above so
direct requests to the license upload folder are also blocked.

Administrators can customize the issuance Property Accountability Form from
**PDF Form Designer**. The visual editor supports rich text formatting, layout
HTML, drag-and-drop section ordering, item-photo placement and sizing, paper
size/orientation, and issuance merge fields. Resize the editor and live preview
independently, or focus either workspace full-screen. The sample preview updates
as the visual or HTML source is edited. Choose a category to start with a
device-focused layout (laptop/PC, phone, keycard, monitor, or the selected
category) and save an independent template for it. Category templates override
the default form for matching issued assets. Run
`php install/database_migrate.php` to create the `pdf_templates` and
`pdf_category_templates` tables. Saved templates are sanitized before storage.
Re-running the migration will not reset an existing administrator password.
Laptop issuance PDFs use the configured default terms. Use the credential
editor beside an issuance's print action to save a username and password for
that specific assignment, regardless of asset category. Saved credentials are
encrypted with the configured license-key encryption key and appear in the
printed PAR when set. Every accountable employee is listed in the recipient and
Received By areas. Run `php install/database_migrate.php` to add the issuance
credential fields to existing databases. Phone and keycard PDFs use their respective
layouts unless a category-specific layout has been saved. The PDF
designer preview mirrors those category layouts. Keycards receive an automatic,
unique inventory ID; recording the physical chip/printed UID is optional, but
any UID entered must be unique. A keycard may be assigned to multiple employees
in one issuance. Choose Grandmaster, Master, Emergency, Floor Master, Room
Master, or enter a custom keycard type, and enter the allowed floor access.
Assignments expire one year after issue. Administrators can configure the
Prepared By and Noted By names and position/titles in **Settings**; leaving a
name blank uses the issuing staff member recorded on the accountability form,
and leaving a position/title blank uses the form default. Run
`php install/database_migrate.php` to backfill employee links and prepare
existing databases for multi-employee keycard assignments. Use **Keycards** in
the sidebar to register physical cards, review available and assigned cards,
monitor expiry, and launch an assignment. Assignment opens Issuance with the
selected card preloaded. Active assignments are excluded from the
available-asset list to prevent assigning a card twice.
For phone/mobile categories, Inventory shows service fields for mobile number,
plan, monthly limit, inclusions, other rates, and activation date. These values
are saved with the asset, shown in the issuance summary, and merged into its
phone form; existing labeled Other Details values such as
`Mobile Number: 09981234567` and `Plan: Corporate` remain supported.
Keycards display the generated asset tag as the system Keycard ID; the
optional physical UID, keycard type, floor access, linked employees, and
one-year validity are recorded for each assignment. Assets with active
assignments cannot be deleted. History deletion is disabled by default;
administrators can explicitly enable permanent deletion of linked history for
sample/test cleanup in Settings. Unreferenced, non-deployed assets can be
deleted normally.
Issuance print output uses the selected paper's full printable area at normal
size, without scaling down the form. The “Prepared By” name comes from the staff
account recorded when the issuance was created.

## Database Notes

The main schema is in `database/schema.sql`. Apply schema changes deliberately with
`php install/database_migrate.php` during installation and upgrades, not as part
of ordinary user requests. The compatibility migrations are safe to re-run;
new one-time migrations live in `install/migrations/` and are tracked in
`schema_migrations`.

Administrators can open **Settings** in the sidebar and enable **Allow
deleting assets with history** to permanently remove linked issuance,
maintenance, and disposal records. This safeguard is disabled by default;
assets with active assignments remain protected. Use it only for sample/test
cleanup.

Write forms use single-use CSRF tokens to reject a browser resubmission of an
already completed POST. This prevents accidental duplicate inserts or stock
adjustments after refreshing or revisiting a submitted form. MySQL data itself
persists across web-server restarts; restarting the server does not reset or
recreate application records.

## White-label and source-code licensing

Administrators can configure the product name, customer organization,
department label, and organization logo in **Settings**. These values are used
in navigation, login, footers, and generated accountability forms; customers
do not need to edit PHP templates just to apply their branding.

This repository does not grant a source-code license by itself. Before selling
copies, use a written customer agreement that defines permitted use, copying,
modification, redistribution, support, updates, warranties, and liability.
Have a qualified attorney review the terms and verify that all code, design,
images, and document templates are owned or licensed for commercial resale.

Before any public release, inspect the complete Git history and Git LFS for
credentials, database exports, uploads, employee information, and confidential
documents. `.gitignore` prevents future additions; it does not remove material
already committed. Prepare a separate clean demo database and non-identifying
screenshots for marketing.

## Release, upgrades, and recovery

See [RELEASE_CHECKLIST.md](./RELEASE_CHECKLIST.md) for release packaging,
source-history review, production configuration, software license-key
encryption, database upgrades, backups, and restore drills. Generate and
configure `ITIMS_LICENSE_KEY_ENCRYPTION_KEY` before applying the migration to
an existing database containing license keys. Keep that key backed up outside
the web root; losing or changing it without controlled re-encryption makes
stored license keys unreadable.

The administrator-only **Reveal key** action is audited. Staff can work with
software license records without receiving stored keys in page data. New and
migrated keys are encrypted with AES-256-GCM.

## Automated checks

GitHub Actions runs PHP 8.5 syntax checks, license-secret regression tests,
MySQL schema migrations twice against an isolated database, and PHP security
regression tests, including dashboard output escaping and PDF issuance
signatories. Run the standalone
encryption tests locally with:

```powershell
php tests\license_secrets_test.php
```

Run the dashboard and routing regression tests with:

```powershell
php tests\dashboard_view_test.php
php tests\app_routes_test.php
```

## Category-Based Asset Fields

Asset categories now control whether:

- a serial number is required
- processor / RAM / storage fields are shown
- notes / other specs are shown

Examples:

- `Laptop`: keep serial number and technical specs enabled
- `RJ45`, `Battery`, `PCB Board`: disable technical specs and make serial optional if not needed

You can manage these rules in `categories.php`.

## New Operational Modules

- `Room Management`: deploy stock items like batteries, RJ45, lockset PCB boards, and similar IT room items to:
  - a room number
  - an employee
  - any custom target/location
- `Software Licenses`: track:
  - contract/payment renewal dates
  - software update due dates
  - owner/assignee
  - seat counts and cost

## Security Checklist (Recommended)

- Move secrets (DB password) out of the repo and into environment variables or a non-committed config file.
- Use prepared statements for all queries (already mostly done).
- Add CSRF protection for all POST actions and avoid destructive actions via GET.
- Escape output in templates consistently (`htmlspecialchars`) to reduce XSS risk.
- Set secure session cookie flags and regenerate session IDs on login.
- Store uploads outside the web root or deny direct access to uploads that should not be public.
