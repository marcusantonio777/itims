# Quest Hotel ITIMS - Full Hardening & Gap Fixes - Implementation Plan

## Task 1: Create includes/auth.php - auth/bootstrap + CSRF + session hardening
- **Status**: `pending`
- **Priority**: high
- **Depends On**: None
- **Description**:
  - Define role constants: `ROLE_ADMIN = 'admin'`, `ROLE_STAFF = 'staff'`, `ROLE_VIEWER = 'viewer'`, `ROLES_ALL = [...]`
  - Call `session_start()` with `session_set_cookie_params()` setting `httponly=1`, `samesite=Lax`, `secure = isset($_SERVER['HTTPS'])`, `lifetime = 0`
  - Define `auth_current_id(): int`, `auth_current_role(): string`, `auth_current_name(): string`, `auth_user_record(): array|null`
  - Define `enforce_authenticated()` — redirects to index.php if no session
  - Define `enforce_role($allowed)` — accepts string or array of roles; redirects with 403 if fail; implies enforce_authenticated
  - Define CSRF token stored in `$_SESSION['csrf']`: `csrf_token(): string`, `csrf_field(): string` (echoes hidden input), `csrf_verify_post(): void` (dies on invalid)
  - Define `e($value): string` shortcut for `htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8')`
  - Define `user_error_log($msg, $ex = null): void` helper that calls PHP `error_log` for exception details
  - Define `generic_error(): string` returns generic safe user message
- **Acceptance Criteria Addressed**: AC-1, AC-3, AC-4 (session foundation), AC-6 (RBAC foundation)
- **Test Requirements**:
  - `rule` TR-1.1: `php -l includes/auth.php` exits with 0 (no syntax errors)
  - `rule` TR-1.2: File defines all 10 symbols listed; constant definitions present; function signatures correct
  - `rubric` TR-1.3: CSRF entropy; scale 1-5; 1=weak/short token; 3=random_bytes(32) bin2hex; 5=random_bytes + per-form variation; threshold >=3; evidence: source of csrf_token()

## Task 2: Refactor index.php - inactive users, session regen, login throttle
- **Status**: `pending`
- **Priority**: high
- **Depends On**: Task 1
- **Description**:
  - Require includes/auth.php at top
  - Fetch user by username; verify password AND `is_active = 1`
  - On success: `session_regenerate_id(true)`; set session vars; log `Login` via `log_activity`
  - Add login throttle: `$_SESSION['login_failures']` count + timestamps; after 5 failures in last 60s, `usleep()` with progressive backoff
  - Replace hardcoded "CRITICAL: AUTH FAILURE" message with generic but professional copy
- **Acceptance Criteria Addressed**: AC-2, AC-3, AC-16
- **Test Requirements**:
  - `rule` TR-2.1: Login with disabled user (`is_active=0`) does NOT set `staff_id`
  - `rule` TR-2.2: 5th consecutive failed login adds >= 200ms sleep delay (code inspection); counter increments stored
  - `rule` TR-2.3: Successful login calls `session_regenerate_id(true)` before redirect

## Task 3: Refactor logout.php - audit + proper teardown
- **Status**: `pending`
- **Priority**: high
- **Depends On**: Task 1
- **Description**:
  - Require includes/auth.php
  - If authenticated: call `log_activity($pdo, 'Logout', 'it_staff', auth_current_id(), null, 'User logout')`
  - `$_SESSION = []`, `session_regenerate_id(true)`, `session_destroy()`
  - If session cookie exists: `setcookie(session_name(), '', time()-3600, ...)` to clear
  - Redirect to index.php
- **Acceptance Criteria Addressed**: AC-3, AC-9
- **Test Requirements**:
  - `rule` TR-3.1: audit_logs gains a `Logout` row when logged-in user visits logout.php
  - `rule` TR-3.2: After logout, session_id is invalidated; cookie cleared if applicable

## Task 4: Refactor config/db.php - no DDL, credential loader, enum expansion, log_activity validation
- **Status**: `pending`
- **Priority**: high
- **Depends On**: Task 1 (uses role constants for default user if needed)
- **Description**:
  - Load credentials: try `require __DIR__ . '/db.local.php'` (define $host,$db,$user,$pass there); if absent use `getenv('ITIMS_DB_*')` else fallback to safe empty (connect will fail)
  - Remove ALL `$pdo->exec("CREATE TABLE ...")` and `$pdo->exec("ALTER TABLE ...")` calls from db.php
  - Move those DDL statements into `install/database_migrate.php` as a standalone run-once admin script
  - Expand `audit_logs.action_type` ENUM to include: `'Create','Update','Delete','Login','Logout','Issue','Return','UserCreate','UserUpdate','UserRoleChange','UserDeactivate','UserReactivate','UserPasswordReset','TermsCreate','TermsUpdate','TermsDelete'`
  - Modify `log_activity()`: validate `$action_type` is in the ENUM set before INSERT; invalid = substitute `'Update'` + `error_log("invalid action_type: ...")`
  - Remove the silent admin123 hash rehash block (lines 168-176)
- **Acceptance Criteria Addressed**: AC-8, AC-10, AC-11
- **Test Requirements**:
  - `rule` TR-4.1: Grep `config/db.php` for `CREATE TABLE|ALTER TABLE` returns 0 matches
  - `rule` TR-4.2: `config/db.php` no longer contains hardcoded strings `$user = 'root'` AND `$pass = ''` on plain lines
  - `rule` TR-4.3: log_activity validation rejects unknown $action_type with error_log + safe fallback

## Task 5: Create install/database_migrate.php - consolidated DDL bootstrap
- **Status**: `pending`
- **Priority**: high
- **Depends On**: Task 4
- **Description**:
  - Self-contained script that uses PDO to connect (require `config/db.php`), then runs the CREATE/ALTER statements removed from db.php idempotently (CREATE TABLE IF NOT EXISTS, ALTER TABLE wrapped in try/catch for existing cols)
  - Expands the audit_logs action_type ENUM via ALTER TABLE (safe ALGORITHM=INPLACE if supported, else plain ALTER)
  - Creates `accountability_terms(id PK, category_id FK categories|null unique pair?, terms_text TEXT NOT NULL, updated_by FK it_staff, updated_at TIMESTAMP, created_at TIMESTAMP)` table schema
  - Creates uploads directories with proper perms (0755 not 0777)
  - Outputs a checklist of "OK" / "Skipped - exists" / "Error - ..." per migration step
  - Admin-only: at top enforce_role(ROLE_ADMIN) OR check CLI execution to prevent public runs
- **Acceptance Criteria Addressed**: AC-1 (terms table), AC-7 (terms feature), AC-11
- **Test Requirements**:
  - `rule` TR-5.1: `php install/database_migrate.php` runs without fatal and creates accountability_terms table
  - `rule` TR-5.2: `SHOW COLUMNS FROM audit_logs LIKE 'action_type'` includes UserCreate, UserRoleChange, TermsCreate, Logout, etc.

## Task 6: Fix users.php - add sidebar link, use auth.php, verify works
- **Status**: `pending`
- **Priority**: high
- **Depends On**: Task 1, Task 4, Task 5
- **Description**:
  - Ensure it `require_once includes/auth.php` at top (existing line, now file exists), `enforce_role(ROLE_ADMIN)`
  - Normalize `log_activity` calls: use expanded ENUM values consistently (or validation will remap but keep proper strings)
  - Add sidebar link to users.php inside header.php (admin-only conditional)
- **Acceptance Criteria Addressed**: AC-1, AC-6, AC-7
- **Test Requirements**:
  - `rule` TR-6.1: `php -l users.php` exit 0
  - `rule` TR-6.2: As admin, GET users.php renders "User Management" heading; as viewer GET 403/redirect

## Task 7: Fix terms.php - add sidebar link, verify missing table, fix log_activity calls
- **Status**: `pending`
- **Priority**: high
- **Depends On**: Task 1, Task 4, Task 5, Task 6
- **Description**:
  - File already requires auth.php, enforce_role(ROLE_ADMIN); verify works now that db/terms table exists
  - The `@log_activity(...)` error suppress calls should call the normal helper (validation in helper handles unknown values)
  - Use expanded ENUM TermsCreate/TermsUpdate/TermsDelete correctly, remove `@` suppression
  - Add sidebar link to terms.php inside header.php (admin-only conditional)
- **Acceptance Criteria Addressed**: AC-1, AC-7
- **Test Requirements**:
  - `rule` TR-7.1: `php -l terms.php` exit 0; GET terms.php (admin) shows the editor UI + "Default (fallback) terms" section
  - `rule` TR-7.2: Save default terms POST succeeds and row persists in accountability_terms

## Task 8: Update includes/header.php - sidebar links (users, terms) + small improvements
- **Status**: `pending`
- **Priority**: high
- **Depends On**: Task 1, Task 6, Task 7
- **Description**:
  - After Software Licenses link, insert admin-only conditional block with two links:
    - `users.php`: "User Management" + icon (users svg)
    - `terms.php`: "Terms Editor" + icon (document svg)
  - Guard the block with `<?php if (auth_current_role() === ROLE_ADMIN): ?>`
  - Replace any raw `$_SESSION['staff_name']` / `$_SESSION['staff_role']` echoes with `e(auth_current_name())` / `e(auth_current_role())`
- **Acceptance Criteria Addressed**: AC-5 (header escaping), AC-6, AC-7
- **Test Requirements**:
  - `rule` TR-8.1: Admin session sidebar includes "User Management" and "Terms Editor" links
  - `rule` TR-8.2: Staff/Viewer session sidebar does NOT include those links

## Task 9: Refactor profile.php - CSRF, session regen after password change
- **Status**: `pending`
- **Priority**: high
- **Depends On**: Task 1, Task 4
- **Description**:
  - Require auth.php, enforce_authenticated
  - Add `csrf_verify_post()` inside both POST branches (`change_password`, `update_profile`)
  - Add `<?php echo csrf_field(); ?>` in both forms
  - After successful password change: `session_regenerate_id(true)`; log `UserPasswordReset` / `Update` audit
  - Escape all template echoes with `e()`
- **Acceptance Criteria Addressed**: AC-4, AC-5, AC-8
- **Test Requirements**:
  - `rule` TR-9.1: POST change_password without CSRF token = rejected (dies / not processed)
  - `rule` TR-9.2: After password change success, session_id changes (inspection of call site)

## Task 10: Refactor maintenance.php - CSRF + escaping + RBAC + Edit/Delete CRUD
- **Status**: `pending`
- **Priority**: high
- **Depends On**: Task 1, Task 4
- **Description**:
  - Require auth.php; `enforce_role(['admin','staff'])` for all writes; enforce_authenticated for GET read-only
  - Add `csrf_verify_post()` inside add_maintenance handler; add `csrf_field()` in form
  - Replace all `echo $l[...]` / `echo $message` / `echo $a[...]` / `echo $v[...]` with `e(...)` helper
  - Add Edit maintenance:
    - GET `?edit=<id>` loads record into modal, populates form
    - POST `edit_maintenance` updates record, re-syncs asset status based on status transitions
  - Add Delete maintenance:
    - POST `delete_maintenance` (CSRF + staff+), with confirmation JS
    - Deleting a maintenance record should NOT revert asset status (log it) to avoid conflicts
  - Replace raw `$e->getMessage()` in catch with generic error + `user_error_log`
- **Acceptance Criteria Addressed**: AC-4, AC-5, AC-6, AC-12, AC-16
- **Test Requirements**:
  - `rule` TR-10.1: POST add_maintenance missing CSRF token = handler returns/dies before INSERT
  - `rule` TR-10.2: 20 echoes in maintenance.php templating all wrapped with `e()` (grep count)
  - `rule` TR-10.3: POST edit_maintenance + valid CSRF + staff role => DB row updates; asset status sync logic runs
  - `rule` TR-10.4: POST delete_maintenance + valid CSRF + staff role => DB row deleted

## Task 11: Refactor disposal.php - CSRF + escaping + RBAC + Edit/Delete CRUD
- **Status**: `pending`
- **Priority**: high
- **Depends On**: Task 1, Task 4
- **Description**:
  - Require auth.php; `enforce_role(['admin','staff'])` for writes
  - Add CSRF verify + field
  - Escape all template echoes with `e()`
  - Add Edit disposal:
    - GET `?edit=<id>` preloads
    - POST `edit_disposal` updates disposal row; if method/reason changes, keep asset Retired but update notes if needed
  - Add Delete disposal:
    - POST `delete_disposal` deletes the disposal record; leaves asset Retired (safe default; log action as audit entry)
  - Generic catch messages, no `$e->getMessage()` to user
- **Acceptance Criteria Addressed**: AC-4, AC-5, AC-6, AC-14, AC-16
- **Test Requirements**:
  - `rule` TR-11.1: dispose_asset POST without CSRF = no DB write
  - `rule` TR-11.2: Edit/Delete handlers exist; viewer role POST to them is rejected by enforce_role

## Task 12: Refactor inventory.php - CSRF + escaping + RBAC
- **Status**: `pending`
- **Priority**: high
- **Depends On**: Task 1, Task 4
- **Description**:
  - Require auth.php; writes enforce staff+; reads enforce authenticated
  - Add CSRF verify + fields for all write branches (add_asset, edit_asset, delete_asset, uploads, category/vendor inline creations)
  - Escape all template echoes with `e()`
  - Replace raw catch `$e->getMessage()` with generic + error_log
  - Keep upload logic but change mkdir `0777` to `0755`
- **Acceptance Criteria Addressed**: AC-4, AC-5, AC-6, AC-16
- **Test Requirements**:
  - `rule` TR-12.1: All POST write handlers call csrf_verify_post() before DB writes
  - `rule` TR-12.2: All template DB echoes use `e()` wrapper

## Task 13: Refactor supplies.php - CSRF + escaping + RBAC
- **Status**: `pending`
- **Priority**: high
- **Depends On**: Task 1
- **Description**:
  - Require auth.php; writes staff+
  - CSRF protection on all POST write handlers
  - All DB echoes escaped via `e()`
  - Generic exception messages
- **Acceptance Criteria Addressed**: AC-4, AC-5, AC-6, AC-16
- **Test Requirements**:
  - `rule` TR-13.1: All write handlers (stock in, stock out, adjust, create, edit, delete) protected
  - `rule` TR-13.2: Template layer all-echo escaped

## Task 14: Refactor issuance.php, employees.php, employee_details.php - CSRF + escaping + RBAC
- **Status**: `pending`
- **Priority**: medium
- **Depends On**: Task 1
- **Description**:
  - Require auth.php; writes staff+
  - CSRF verify on all write POST handlers
  - Escape template echoes
  - Generic error messages (no `$e->getMessage()` in user output)
- **Acceptance Criteria Addressed**: AC-4, AC-5, AC-6, AC-16
- **Test Requirements**:
  - `rule` TR-14.1: Write forms (add/edit/delete) contain CSRF hidden inputs; handlers verify
  - `rule` TR-14.2: No raw echo of DB data

## Task 15: Refactor vendors.php, categories.php - CSRF + escaping + RBAC
- **Status**: `pending`
- **Priority**: medium
- **Depends On**: Task 1
- **Description**:
  - Require auth.php; writes staff+ (admin recommended but staff acceptable per current matrix)
  - CSRF + escaping + generic errors
- **Acceptance Criteria Addressed**: AC-4, AC-5, AC-6, AC-16
- **Test Requirements**:
  - `rule` TR-15.1: CRUD handlers (add/edit/delete) CSRF protected
  - `rule` TR-15.2: Lists of vendors/categories escape all echoed DB columns

## Task 16: Refactor room_management.php, software_licenses.php - CSRF + escaping + RBAC
- **Status**: `pending`
- **Priority**: medium
- **Depends On**: Task 1
- **Description**:
  - Require auth.php; writes staff+
  - CSRF + escaping + generic errors
- **Acceptance Criteria Addressed**: AC-4, AC-5, AC-6, AC-16
- **Test Requirements**:
  - `rule` TR-16.1: All write branches (deploy, edit, delete, license CRUD) CSRF protected
  - `rule` TR-16.2: Template echoes escaped

## Task 17: Refactor dashboard.php, reports.php, activity_logs.php, asset_details.php, generate_pdf.php - escaping + RBAC
- **Status**: `pending`
- **Priority**: medium
- **Depends On**: Task 1
- **Description**:
  - Require auth.php; enforce_authenticated
  - Escape all echoes
  - If reports/asset_details/generate_pdf have any write hooks, add CSRF
- **Acceptance Criteria Addressed**: AC-5, AC-6
- **Test Requirements**:
  - `rule` TR-17.1: dashboard counters, chart labels, log rows in activity_logs all escaped
  - `rule` TR-17.2: generate_pdf.php uses e() before any PDF text writes (or similar for TCPDF/HTML-to-text)

## Task 18: Harden uploads/ directory with .htaccess
- **Status**: `pending`
- **Priority**: medium
- **Depends On**: Task 12 (for mkdir change complement)
- **Description**:
  - Create `uploads/.htaccess` with:
    - `Options -ExecCGI -Indexes`
    - `SetHandler default-handler`
    - `php_flag engine off`
    - `FilesMatch \.(?:php[0-9]?|phtml|phar|pl|py|cgi|sh|jsp|asp|aspx)$` -> `Require all denied`
    - (Optional) Allow only specific image MIME types via mod_mime or ForceType for `image/*` and `application/pdf`
  - Create sub-dir uploads/assets/.htaccess (or inherit from parent)
- **Acceptance Criteria Addressed**: AC-15
- **Test Requirements**:
  - `rule` TR-18.1: `uploads/.htaccess` file exists with the above directives
  - `rule` TR-18.2: Grep `php_flag engine off` in uploads/.htaccess present

## Task 19: Fix remaining catch-block exception leaks across all pages
- **Status**: `pending`
- **Priority**: medium
- **Depends On**: Task 10,11,12,13,14,15,16,17 (batches the pages)
- **Description**:
  - Sweep every remaining catch (PDOException $e) block that echoes `$error = $e->getMessage()` to user
  - Replace pattern: `error_log(... $e->getMessage()); $error = generic_error();`
  - Pages to scan: all not covered above, including any extras like fix_database.php, create_disposal_table.php (admin/dev tools - at least sanitize output)
- **Acceptance Criteria Addressed**: AC-16
- **Test Requirements**:
  - `rule` TR-19.1: Grep `'$error = .*getMessage\(\)'` returns 0 matches across all PHP files in project
  - `rule` TR-19.2: Each catch block contains either `error_log` call or `user_error_log` invocation before generic message

## Task 20: Final verification pass - syntax lint for all PHP files + spot-check XSS/CSRF
- **Status**: `pending`
- **Priority**: medium
- **Depends On**: Tasks 1 through 19 all complete
- **Description**:
  - Run `php -l` against every *.php file; fix any syntax errors
  - Re-grep for remaining raw echoes of DB vars that might have been missed
  - Verify sidebar renders expected links for admin vs staff vs viewer
- **Acceptance Criteria Addressed**: All (regression pass across all ACs)
- **Test Requirements**:
  - `rule` TR-20.1: `Get-ChildItem -Path . -Filter *.php -Recurse | % { php -l $_.FullName }` all exit 0
  - `rubric` TR-20.2: Escape coverage; scale 1-5; anchors 1 = many unescaped; 3 = majority done; 5 = every echo escaped; threshold >= 4; evidence: spot check echoes in every page's main tables
  - `rubric` TR-20.3: CSRF coverage; scale 1-5; anchors 1 = many unprotected; 3 = majority done; 5 = every write form protected; threshold >= 4
