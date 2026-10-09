# Changelog

All notable changes to this project will be recorded here.

## Unreleased

- Encrypt software license keys at rest and limit key reveal to administrators.
- Add version tracking for forward database migrations and ensure attachment
  fields exist on fresh and upgraded databases.
- Validate software license attachments by size, extension, and detected MIME
  type, and avoid orphaned files when record updates fail.
- Add automated PHP, MySQL migration, TypeScript, and dashboard build checks.
