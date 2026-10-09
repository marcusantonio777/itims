# Release and Operations Checklist

## Before a customer release

- Keep this source repository private until the entire Git history has been
  reviewed for customer names, logos, documents, sample records, credentials,
  and other confidential material. The current history contains the former
  customer logo. `.gitignore` does not erase previously committed content.
- Run a history-aware secret scan before publishing (for example,
  `gitleaks git --log-opts="--all"`), investigate every finding, and rotate any
  exposed credentials before considering a history rewrite.
- For a public release, create a new clean repository from reviewed source or
  use a history-rewrite tool only after backing up the existing repository and
  coordinating a force-push with every collaborator. Do not rewrite the current
  customer repository casually.
- Verify ownership or commercial licensing for source, images, fonts, PDF
  templates, and third-party dependencies. Obtain legal review of the customer
  software license agreement; this project does not enforce a commercial
  activation key.
- Install a tagged release into a fresh database and separately upgrade a copy
  of a representative existing database. Exercise login, permissions, asset
  issuance/return, keycards, uploads/previews, and PDF generation.
- Build the release from a clean, reviewed, signed-off tag:

  ```powershell
  .\install\build-release.ps1 -Tag v1.2.3
  ```

  The script refuses dirty worktrees. The resulting ZIP excludes Git metadata,
  ignored local uploads, local credentials, and `node_modules`; it contains the
  committed compiled dashboard assets. Review the archive contents before
  distribution.

## Production configuration

- Use HTTPS, a dedicated least-privileged MySQL account, unique administrator
  credentials, and a supported PHP/MySQL version.
- Keep `config/db.local.php`, the license-key encryption key, backups, logs, and
  customer uploads outside public source control. Prefer environment variables
  supplied by the hosting service.
- Generate a license-key encryption key using:

  ```powershell
  php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"
  ```

  Set it as `ITIMS_LICENSE_KEY_ENCRYPTION_KEY` before applying migrations. Save
  it in a secrets manager and include it in encrypted backups. Losing it makes
  encrypted license keys unrecoverable. Do not rotate it unless every stored
  key is deliberately decrypted and re-encrypted.
- For upgrades with existing plaintext license keys, take and test a database
  backup first, configure the encryption key, deploy the release, and run:

  ```powershell
  php install\database_migrate.php
  ```

  The migration encrypts existing keys and records its version. The older
  compatibility migrations remain idempotent and are also checked each run.
- Remove or deny web access to setup and migration endpoints when they are no
  longer needed. Restrict `config/` and all `uploads/` directories at the web
  server; verify the rules on the actual Apache/Nginx deployment.

## Backup and restore

- Back up the database, all required `uploads/` content, local configuration,
  and the encryption key together. Store backups encrypted and access-controlled.
- On XAMPP, create a protected backup directory and run `mysqldump` with an
  account that can read the application database. `--password` prompts instead
  of placing the database password in shell history:

  ```powershell
  New-Item -ItemType Directory -Path 'D:\Backups\ITIMS' -Force
  & 'C:\xampp\mysql\bin\mysqldump.exe' --host=localhost --user=itims_backup --password --single-transaction --routines --triggers --databases itims_db --result-file='D:\Backups\ITIMS\itims.sql'
  Copy-Item '.\uploads' 'D:\Backups\ITIMS\uploads' -Recurse -Force
  Copy-Item '.\config\db.local.php' 'D:\Backups\ITIMS\db.local.php' -Force
  ```

  Keep the encryption key in the same protected backup set, and retain the
  exact application release/version alongside each backup.
- Test restoration on an isolated database and application copy before relying
  on the backup. Confirm files, branding, attachments, encrypted license keys,
  and account access after restore.
- For a MySQL client restore, start an interactive client (it prompts for the
  password), then run `SOURCE D:/Backups/ITIMS/itims.sql;`. Restore the saved
  uploads and local configuration only into the isolated copy during a drill.
- Define a retention period and perform periodic restore drills. A backup that
  has never been restored is not a verified recovery plan.

## Release verification

- Require CI success for PHP syntax, license-secret tests, MySQL migration
  reruns, dashboard type-check, and dashboard build.
- Record customer-visible changes in `CHANGELOG.md`; tag releases as
  `vMAJOR.MINOR.PATCH`.
- Publish a checksum for each customer archive and retain the exact source tag
  used to produce it.
