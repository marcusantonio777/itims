 # ITIMS (PHP + MySQL)

ITIMS is a PHP/MySQL application for managing IT assets, supplies, employee
accountability, keycards, maintenance, deployments, software licenses, and
audit history.

This repository contains application source and the install schema. It does
not contain production data or required user-uploaded files. Keep the GitHub
repository private unless deployment-specific credentials, defaults, content,
and ownership have been reviewed for public release.

## Requirements

- PHP 8.x
- MySQL 8.x (or compatible MariaDB)

## Quick Start (Local)

1. Install XAMPP with PHP 8.x and MySQL/MariaDB, then start Apache and MySQL.

2. Open phpMyAdmin, create the `itims_db` database, select it, and import
   `database.sql`.

3. Configure local database credentials:

   - Copy `config/db.local.example.php` to `config/db.local.php`.
   - Update the local file with your database credentials. This file is ignored
     by Git; do not commit it.
   - For production, use a dedicated MySQL user with least privilege (not
     `root`).

4. Run the database migration once (also required when upgrading an existing database):

   ```bash
   php install/database_migrate.php
   ```

   This ensures required supporting tables exist, including linked employee
   recipients for shared accountability records, adds separate Keycards page
   permissions, and normalizes legacy `Admin` / `Tech` roles to `admin` /
   `staff` / `viewer`.

5. Run the app:

   ```bash
   php -S localhost:8000 -t .
   ```

   Run this command from the project directory. With a standard XAMPP install,
   the PHP executable is `C:\xampp\php\php.exe`.

6. Open:

   - http://localhost:8000/

The initial administrator created by the schema/migration uses username
`admin` and password `admin123`. Change it immediately after first sign-in.
Never reuse this bootstrap password in a deployed environment.

## Production Security

- Re-run `php install/database_migrate.php` after upgrading so the persistent
  login rate-limit table is created.
- Serve the application only over HTTPS in production. Session cookies are
  marked secure when HTTPS is detected.
- Change the initial `admin` password immediately after first sign-in, use a
  unique password, and grant the database account only the permissions the
  application requires.
- Keep `config/db.local.php` out of the web root's public access; Apache is
  configured to deny direct requests to the `config` directory.
- Never commit local uploads, database dumps, credentials, or production
  employee/asset records. The repository `.gitignore` excludes local
  configuration and uploaded content.

## Project Structure

- `index.php`: login page
- `dashboard.php`: dashboard metrics and shortcuts
- `inventory.php`: asset CRUD + photo uploads
- `supplies.php`: supply CRUD + stock movement logs
- `issuance.php`: asset/supply issuance + accountability logs
- `room_management.php`: deploy room-related IT supplies to rooms, employees, or custom targets
- `maintenance.php`: maintenance logging
- `reports.php`: reports and exports
- `software_licenses.php`: track software contracts, renewals, payments, and update schedules
- `activity_logs.php`: audit trail viewer
- `file_manager.php`: authenticated shared file explorer for managed files
- `config/db.php`: database connection and shared helpers
- `includes/`: shared layout (`header.php`, `footer.php`)
- `uploads/`: user uploads (asset photos, scanned files, etc.)

The file explorer stores managed files under `uploads/file_manager/`, blocks
direct web access to that directory, and serves files only through the
authenticated application. Run `php install/database_migrate.php` to create
its metadata table and storage directory. Images open in a zoomable, rotatable
preview modal; PDFs and plain text/CSV files preview in-app, while other
formats are offered as authenticated downloads.

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
Laptop issuance PDFs retain the original PAF layout and configured default
terms. The computer login username comes from the receiving employee record;
the existing default password remains `questhotel`, and the form reminds the
employee to change it. Phone and keycard PDFs follow their respective printed
reference layouts unless a category-specific layout has been saved. The PDF
designer preview mirrors those category layouts. Keycard assignments require
one employee and a card level (Grandmaster, Master, Emergency, Floor Master,
Room Master, or a custom level); access is recorded as All Floors and the
expiry is set to one year after assignment. Run
`php install/database_migrate.php` to add the keycard assignment fields to
existing databases. Use **Keycards** in the sidebar to register physical cards,
review available and assigned cards, monitor expiry, and launch an assignment.
Assignment opens Issuance with the selected card preloaded. Active assignments
are excluded from the available-asset list to prevent assigning a card twice.
For phone/mobile categories, Inventory shows service fields for mobile number,
plan, monthly limit, inclusions, other rates, and activation date. These values
are saved with the asset, shown in the issuance summary, and merged into its
phone form; existing labeled Other Details values such as
`Mobile Number: 09981234567` and `Plan: Corporate` remain supported.
Keycards use the asset's Card ID/serial field; the level, employee, and
one-year validity are recorded when the card is assigned. Assets with active
assignments cannot be deleted. History deletion is disabled by default;
administrators can explicitly enable permanent deletion of linked history for
sample/test cleanup in Settings. Unreferenced, non-deployed assets can be
deleted normally.
Issuance print output uses the selected paper's full printable area at normal
size, without scaling down the form. The “Prepared By” name comes from the staff
account recorded when the issuance was created.

## Database Notes

The main schema is in `database.sql`. Some pages/scripts also attempt to create/alter tables at runtime. For production deployments, it’s safer to manage schema changes using explicit SQL migrations rather than running DDL automatically on every request.

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

## Public-release checklist

Before changing a private GitHub repository to public:

- Remove or replace the known `admin` / `admin123` bootstrap credential and
  decide how first-admin setup will work.
- Remove or replace the `questhotel` default computer password in the issuance
  workflow and examples.
- Inspect every commit and Git LFS object for credentials, database exports,
  uploads, employee information, PARs, and other confidential material. A
  `.gitignore` change does not remove files already committed.
- Confirm Quest Hotel branding, source code, images, and templates are approved
  for public release and choose an appropriate license with the owner.
- Rotate any credentials that may have been committed and review GitHub's
  secret-scanning alerts.
- Use a clean demo database and non-identifying screenshots for public
  documentation.

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
