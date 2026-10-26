# Quest Hotel ITIMS - Full Hardening & Gap Fix - Product Requirements Document

## Overview
- **Summary**: End-to-end security hardening, broken-functionality repairs, feature-parity completion, and code-quality improvements for the Quest Hotel IT Inventory Management System (ITIMS) PHP/MySQL application.
- **Purpose**: Eliminate critical crashes (missing `includes/auth.php`), systemic CSRF and XSS vulnerabilities, missing RBAC enforcement, broken login flows, and hidden admin features. Add defensive session hardening, activity-log integrity, audit-trail completeness, DB credential management, and CRUD parity for maintenance/disposal modules.
- **Target Users**: Quest Hotel IT Administrators, IT Staff, and Viewers (auditors / read-only operators).

## Goals
- Every page that currently crashes (`users.php`, `terms.php`) loads correctly with its full intended functionality.
- Every authenticated page respects role-based access control (admin/staff/viewer) consistently.
- Every state-changing form is protected by a CSRF token and every template output is escaped.
- Sessions are hardened (fixation-resistant, cookie flags set, inactive users rejected, logout audited).
- DB credentials are managed outside hardcoded strings and defaults admin password is not silently auto-set.
- `log_activity()` always writes a value that is valid for the ENUM and audit trail is complete for Login/Logout.
- Admin User Management is discoverable via sidebar, not only via direct URL.
- Maintenance records and Disposal records support Edit/Delete with proper authorization.
- Runtime DDL in `config/db.php` is minimized: create a migration-style bootstrap script and keep db.php lean.
- Generic error messages do not leak raw PDO exceptions or SQL details to end users.

## Non-Goals
- Rewrite the app into a framework (Laravel/Symfony). Keep the current flat-PHP architecture.
- Add an SMTP-based email notification subsystem (dashboard counters remain passive; no email delivery).
- Add full-text search server (ElasticSearch/Lucene). Client-side search or simple SQL `LIKE` is acceptable.
- Add payments, SSO, OAuth, or multi-tenancy.
- Rewrite visual design / branding; only fix broken UX elements.

## Background & Context
Repository root: `c:\xampp\htdocs\itims\`. The README at [README.md](file:///c:/xampp/htdocs/itims/README.md) recommends, but does not enforce, CSRF protection, output escaping, and secure sessions. Current analysis (2026-10-08) identified three CRITICAL crash/visibility issues, eight HIGH systemic security issues, seven MEDIUM operational/feature gaps, and four LOW architecture/code-quality items.

Key existing modules (all flat PHP pages):
- Auth & Session: [index.php](file:///c:/xampp/htdocs/itims/index.php), [logout.php](file:///c:/xampp/htdocs/itims/logout.php), [profile.php](file:///c:/xampp/htdocs/itims/profile.php)
- Layout: [includes/header.php](file:///c:/xampp/htdocs/itims/includes/header.php), [includes/footer.php](file:///c:/xampp/htdocs/itims/includes/footer.php)
- DB: [config/db.php](file:///c:/xampp/htdocs/itims/config/db.php) (also auto-DDL + log_activity helper)
- Broken pages referencing missing auth.php: [users.php](file:///c:/xampp/htdocs/itims/users.php), [terms.php](file:///c:/xampp/htdocs/itims/terms.php)
- Write-heavy pages needing CSRF + RBAC + escaping: [maintenance.php](file:///c:/xampp/htdocs/itims/maintenance.php), [inventory.php](file:///c:/xampp/htdocs/itims/inventory.php), [disposal.php](file:///c:/xampp/htdocs/itims/disposal.php), [supplies.php](file:///c:/xampp/htdocs/itims/supplies.php), [issuance.php](file:///c:/xampp/htdocs/itims/issuance.php), [employees.php](file:///c:/xampp/htdocs/itims/employees.php), [vendors.php](file:///c:/xampp/htdocs/itims/vendors.php), [categories.php](file:///c:/xampp/htdocs/itims/categories.php), [room_management.php](file:///c:/xampp/htdocs/itims/room_management.php), [software_licenses.php](file:///c:/xampp/htdocs/itims/software_licenses.php), [asset_details.php](file:///c:/xampp/htdocs/itims/asset_details.php), [employee_details.php](file:///c:/xampp/htdocs/itims/employee_details.php)
- Other: [dashboard.php](file:///c:/xampp/htdocs/itims/dashboard.php), [reports.php](file:///c:/xampp/htdocs/itims/reports.php), [activity_logs.php](file:///c:/xampp/htdocs/itims/activity_logs.php), [generate_pdf.php](file:///c:/xampp/htdocs/itims/generate_pdf.php)

## Functional Requirements
- **FR-1**: Create `includes/auth.php` exposing: `auth_current_id()`, `auth_current_role()`, `auth_current_name()`, `auth_user_record()`, `enforce_authenticated()`, `enforce_role($allowedRoles)`, `csrf_token()`, `csrf_field()`, `csrf_verify_post()`, plus session bootstrap (hardening + flags).
- **FR-2**: Refactor every authenticated page to `require_once includes/auth.php` and call `enforce_authenticated()` (or `enforce_role(...)` for admin-only pages).
- **FR-3**: Login at `index.php` must reject users where `is_active != 1` and must regenerate session id on success and log a `Login` audit entry.
- **FR-4**: `logout.php` must log a `Logout` audit entry, regenerate and destroy session, clear the session cookie, then redirect.
- **FR-5**: `users.php` (User Management) is accessible only to admins and works without PHP errors.
- **FR-6**: The sidebar in `includes/header.php` shows a "User Management" item when the current user has `role === 'admin'`, and shows "Terms Editor" when admin. Sidebar item for Terms Editor must also exist.
- **FR-7**: `terms.php` must create the `accountability_terms` table if missing (or ensure migration script does) and load/edit/delete terms without errors.
- **FR-8**: Every form that triggers a database write (INSERT/UPDATE/DELETE) in every PHP page must include a CSRF token (via `csrf_field()`) and the handler must call `csrf_verify_post()` before processing.
- **FR-9**: Every echo of user-controlled data (DB data, GET/POST values reflected) must pass through `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` or a wrapper `e(...)`.
- **FR-10**: `log_activity()` in `config/db.php` must accept only ENUM-valid action types. New helpers `log_activity_raw($action, ...)` or an expanded ENUM list is acceptable, but each call site must use a valid value. Add all missing custom action types used by `users.php` to the ENUM (UserCreate, UserUpdate, UserRoleChange, UserDeactivate, UserReactivate, UserPasswordReset, TermsCreate, TermsUpdate, TermsDelete) or normalize calls to existing ENUM values.
- **FR-11**: Role enforcement matrix on write actions:
  - Admin: all actions (CRUD on users, terms, assets, supplies, employees, vendors, categories, issuances, maintenance, disposal, rooms, licenses, reports, logs).
  - Staff: CRUD on assets / supplies / employees / vendors / categories / issuances / maintenance / disposal / rooms / licenses; read reports/logs; cannot modify users/terms.
  - Viewer: read-only on inventory/supplies/employees/dashboard/maintenance history/disposal history/licenses/logs; cannot write anything.
- **FR-12**: Maintenance records support Edit and Delete with staff+ role.
- **FR-13**: Disposal records support Edit and Delete with staff+ role.
- **FR-14**: Remove the hardcoded root/blank credentials from `config/db.php` source. Source them from environment, a `config/.env.local.php` or `config/db.local.php`, with safe defaults failing loudly rather than silently. Remove the `admin123` silent rehash block.
- **FR-15**: Extract runtime DDL from `config/db.php` into `install/database_migrate.php` (or similar) which is run-once or admin-only. `config/db.php` after the change only connects and defines helpers.
- **FR-16**: All catch blocks for PDO exceptions must log the real exception (to `error_log`), and show a generic user-facing error message ("Operation failed. Please try again or contact support.") instead of `$e->getMessage()`.
- **FR-17**: Protect `uploads/` directory: add an `.htaccess` denying direct execution/script interpretation and allow only static asset MIME types. Keep asset photo serving via PHP passthrough optional for now, but at minimum prevent script execution inside uploads.
- **FR-18**: `profile.php` uses CSRF for both "change password" and "update profile" forms. After password change, session is regenerated.
- **FR-19**: Login attempts: add a naive per-IP throttle (failures tracked in `$_SESSION` or a simple file/memory map) — at minimum a sleep + progressive delay after 5 failures within a minute, to avoid completely open brute force.

## Non-Functional Requirements
- **NFR-1**: No new Composer / npm dependencies. Everything in pure PHP 8.x + existing CDN frontend.
- **NFR-2**: Backward compatibility: all existing links, form actions, GET parameters, DB schema (aside from ENUM expansions and new table creation) remain operational.
- **NFR-3**: Performance: page-to-page latency must not increase more than ~15% on typical pages (auth bootstrap overhead only; no per-request DDL).
- **NFR-4**: Security: OWASP Top-10 mitigations in scope for this app: A01 Broken Access Control, A02 Cryptographic Failures (session), A03 Injection (escaping + prepare), A04 Insecure Design (CSRF + enum), A05 Security Misconfiguration (uploads, db creds), A07 Identification and Authentication Failures (inactive logins, logout flow, session regen).
- **NFR-5**: Maintainability: defensive helper functions live in one place (`includes/auth.php` + `config/db.php`) and pages use them consistently. No duplicate CSRF or escaping boilerplate copy-pasted per file.

## Constraints
- **Technical**: PHP 8.x, MySQL 8.x / MariaDB. No ORM, no framework. Modify existing pages in-place.
- **Business**: Do not break any existing CRUD flows that work. Admin role must always exist with at least one active account.
- **Dependencies**: Existing CDN scripts (Tailwind CDN, Chart.js, Google Fonts) remain. No local bundler introduced.

## Assumptions
- `.htaccess` files are honored by Apache (XAMPP default). If running under another server, equivalent Nginx rules are a follow-up.
- The MySQL user can ALTER TABLE to expand ENUM columns and CREATE TABLE for `accountability_terms`.
- The project is deployed with `register_globals=Off` and standard PHP 8 defaults.
- No existing production data must be lost; schema changes are additive-only.

## Open Questions
- None currently. All decisions from prior analysis are assumed accepted.

## Acceptance Criteria

### AC-1: Missing auth.php no longer crashes users/terms pages
- **Type**: `rule`
- **Given**: A browser or CLI request to `users.php` or `terms.php` with a valid admin session
- **When**: PHP evaluates the file
- **Then**: No "Failed opening required 'includes/auth.php'" Fatal Error occurs, and the page renders its UI
- **Pass Condition**: HTTP 200, no PHP fatal error in logs, main heading and tables render
- **Evidence**: Grep for `require_once.*auth\.php` resolves; `php -l users.php && php -l terms.php` exit 0; a browser GET renders User Management heading

### AC-2: Inactive users cannot log in
- **Type**: `rule`
- **Given**: A user exists with `is_active = 0` and correct password
- **When**: `index.php` receives a valid POST with that user's credentials
- **Then**: Login fails with a generic "invalid credentials" style message; no session `staff_id` is set
- **Pass Condition**: After POST, `$_SESSION['staff_id']` remains unset and response body contains error banner
- **Evidence**: SQL sets `is_active=0` for a test account; form POST returns error; no redirect to dashboard

### AC-3: Session fixation resistance + secure cookie flags
- **Type**: `rule`
- **Given**: A valid login flow at `index.php`
- **When**: A successful login occurs
- **Then**: `session_regenerate_id(true)` is invoked, session cookie carries `httponly=1`, `samesite=Lax`, `secure=1` when HTTPS, `lifetime > 0`
- **Pass Condition**: In auth bootstrap, session options contain secure flags; session id differs pre/post-login
- **Evidence**: Code inspection of auth.php session_set_cookie_params + session_regenerate_id calls

### AC-4: CSRF protection on every write form
- **Type**: `rule`
- **Given**: Any page containing a `<form method="POST">` that writes to DB
- **When**: The form handler executes
- **Then**: The handler verifies a CSRF token via `csrf_verify_post()` before executing writes, and the form contains a hidden `<input>` with valid token
- **Pass Condition**: Every POST handler with DB writes calls `csrf_verify_post()` before INSERT/UPDATE/DELETE; every POST form contains `csrf_field()` output
- **Evidence**: Repository grep shows no write-handler without `csrf_verify_post` and no write-form without `csrf_field`

### AC-5: Output escaping completeness
- **Type**: `rubric`
- **Dimension**: Template output of untrusted data is escaped with `htmlspecialchars` via `e()` wrapper or direct calls
- **Scale**: 1-5
- **Anchors**: 1 = multiple obvious XSS vectors remain in high-risk pages (maintenance, inventory, employees); 3 = >70% of echoes escaped; 5 = every echo of DB/user data escapes via a consistent helper
- **Pass Threshold**: >= 4
- **Evidence**: Spot-check 5 echoes per high-risk page (maintenance.php, inventory.php, disposal.php, supplies.php, employees.php, vendors.php, categories.php, issuance.php, room_management.php, software_licenses.php, asset_details.php, employee_details.php, profile.php, dashboard.php, reports.php, activity_logs.php, generate_pdf.php, index.php)

### AC-6: RBAC write enforcement
- **Type**: `rule`
- **Given**: A user with `role === 'viewer'` holds a valid authenticated session
- **When**: They submit any POST write action (add/edit/delete users, terms, assets, supplies, employees, issuances, maintenance, disposal, categories, vendors, room deployments, software licenses)
- **Then**: The action is rejected with HTTP 403 or a redirect + "not authorized" error, and no DB row is mutated
- **Pass Condition**: Every write handler enforces `enforce_role(['admin','staff'])` or admin-only as required, and no writes occur for viewer
- **Evidence**: Manual role check + submit test or code inspection that `enforce_role` is called before each write branch

### AC-7: User Management sidebar visibility + access
- **Type**: `rule`
- **Given**: An admin session on any page
- **When**: The sidebar renders
- **Then**: A "User Management" link pointing to `users.php` and a "Terms Editor" link pointing to `terms.php` appear in the sidebar
- **Pass Condition**: Links appear in header.php sidebar only for admin role, and URLs resolve correctly
- **Evidence**: Admin role session renders both links; staff/viewer do not

### AC-8: log_activity always writes ENUM-valid action_types
- **Type**: `rule`
- **Given**: Any call to `log_activity()` with a $action_type
- **When**: MySQL executes the INSERT
- **Then**: The value exists in `audit_logs.action_type` ENUM definition
- **Pass Condition**: ENUM is expanded to include all values in use, OR call sites only pass values already defined; no warnings about truncated ENUM
- **Evidence**: MySQL `SHOW COLUMNS FROM audit_logs LIKE 'action_type'` lists every action_type string currently in calls, and grep of log_activity calls matches those ENUM options

### AC-9: Logout is audited
- **Type**: `rule`
- **Given**: A valid authenticated session
- **When**: User visits `logout.php`
- **Then**: One `audit_logs` row is inserted with action_type `Logout` for the current staff_id, before session is destroyed
- **Pass Condition**: After logout, a `Logout` row exists for that user with IP + timestamp
- **Evidence**: audit_logs query returns Logout row after hitting logout.php

### AC-10: DB credentials not hardcoded as root/blank
- **Type**: `rule`
- **Given**: A fresh checkout of the repository
- **When**: `config/db.php` is opened
- **Then**: Source code does not contain `$user = 'root'` + `$pass = ''` hardcoded; credentials come from an uncommitted local config or environment, with clear instructions for setup
- **Pass Condition**: `$user/$pass` are read from getenv() or a require of config/db.local.php; the file db.local.php is gitignored if added
- **Evidence**: Code inspection of config/db.php plus presence of a bootstrap (env loader or local config require)

### AC-11: No per-request DDL in db.php
- **Type**: `rule`
- **Given**: Any authenticated page load
- **When**: `config/db.php` is required
- **Then**: No CREATE TABLE / ALTER TABLE statements execute
- **Pass Condition**: db.php contains zero exec()/query() calls with DDL; DDL lives in a separate migration script instead
- **Evidence**: Grep of `config/db.php` for `CREATE TABLE|ALTER TABLE` returns zero matches

### AC-12: No raw PDO exception messages leaked to user
- **Type**: `rule`
- **Given**: Any catch (PDOException $e) block that sets an `$error` variable echoed to users
- **When**: The exception fires
- **Then**: The user-facing message is a generic "Operation failed" string; the raw `$e->getMessage()` goes only to `error_log()`
- **Pass Condition**: Grep for `$error = .*getMessage\(\)` returns zero matches; error_log() is called instead
- **Evidence**: Code inspection of maintenance.php, inventory.php, disposal.php, supplies.php, users.php, terms.php, and remaining catch blocks

### AC-13: Maintenance CRUD parity + authorization
- **Type**: `rule`
- **Given**: A staff+ authenticated session on maintenance.php
- **When**: User chooses to Edit or Delete an existing maintenance record
- **Then**: Edit form preloads existing values; save updates the row (and re-syncs asset status); delete removes the row (with confirmation + CSRF). Viewer cannot perform either action.
- **Pass Condition**: GET `?edit=<id>` renders edit form; POST `edit_maintenance` handler updates DB; POST `delete_maintenance` handler deletes; role enforced
- **Evidence**: UI shows Edit/Delete buttons for staff+; manual form submit confirms DB row updated/deleted; viewer role blocked

### AC-14: Disposal CRUD parity + authorization
- **Type**: `rule`
- **Given**: A staff+ authenticated session on disposal.php
- **When**: User chooses to Edit or Delete an existing disposal record
- **Then**: Edit form preloads; save updates the disposal row and re-syncs asset status if needed; delete removes disposal row and restores asset status if disposed record is removed (or leaves it retired with a logged note — behavior must be deterministic). Viewer cannot perform either action.
- **Pass Condition**: Edit/Delete buttons render for staff+; handler paths exist with CSRF + role; DB rows change correctly
- **Evidence**: UI + handlers work as described; viewer POST blocked

### AC-15: Uploads directory hardened against script execution
- **Type**: `rule`
- **Given**: An Apache/XAMPP setup with `.htaccess` enabled
- **When**: A request arrives for any `.php`, `.phtml`, `.pl`, `.py`, `.cgi` inside `uploads/`
- **Then**: The server returns 403 Forbidden instead of executing the script
- **Pass Condition**: `uploads/.htaccess` (or root-level rule) denies handlers for scripts and limits serving to image/pdf static MIME types
- **Evidence**: `uploads/.htaccess` present with `SetHandler default-handler`, `Options -ExecCGI`, and/or `FilesMatch` deny rules

### AC-16: Login throttle / no infinite brute force
- **Type**: `rubric`
- **Dimension**: Anti-bruteforce on login endpoint
- **Scale**: 1-5
- **Anchors**: 1 = no throttling at all; 3 = simple sleep after N failures; 5 = per-IP lockout window + progressive backoff persisted across requests
- **Pass Threshold**: >= 3
- **Evidence**: Login handler contains throttling logic (sleep + counter) with evidence of 5-failure delay
