# Contributing

## Development setup

Follow the local setup instructions in [README.md](README.md). Copy
`config/db.local.example.php` to `config/db.local.php` and use a local,
disposable database. Never commit local credentials, production data, or files
from `uploads/`.

## Before opening a pull request

- Run PHP syntax checks on changed files (`php -l path/to/file.php`).
- Test the affected workflow using non-production records.
- Run `php install/database_migrate.php` against a disposable database when
  schema or migration code changes.
- Describe behavior changes and any required deployment steps.

Keep changes focused, preserve existing records and permissions, and include
appropriate CSRF validation for state-changing forms.
