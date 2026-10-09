# Security Policy

## Supported versions

Security fixes are currently applied to the latest working version on the
default branch.

## Reporting a vulnerability

Do not disclose security issues, credentials, employee information, or
production data in public issues or discussions. For a private repository,
contact the repository owner directly using GitHub's private communication
options and include:

- A concise description of the affected area
- Reproduction steps that use test data only
- The potential impact
- Any suggested mitigation

Do not include real credentials, employee data, or uploaded production
documents in a report.

## Deployment reminders

- Change all bootstrap passwords before use.
- Use a dedicated MySQL account with least privilege.
- Serve the application over HTTPS.
- Keep local configuration, database exports, logs, and uploads out of Git.
- Apply schema migrations and review destructive settings before production use.
- Configure a unique 32-byte Base64 encryption key using
  `ITIMS_LICENSE_KEY_ENCRYPTION_KEY` before migrating existing software license
  keys. Back it up separately and securely; changing or losing it makes stored
  keys unreadable.
- Only administrators can reveal stored software license keys. Reveals are
  logged without writing the key value to the audit record.
- Keep uploaded files behind server access controls and retain the application's
  MIME, extension, and size validation. Test these protections on the production
  web server rather than relying only on local `.htaccess` behavior.

Before selling or publishing a release, review every Git history ref and Git
LFS object for customer branding, private records, credentials, and documents.
Removing a file from the current branch does not erase earlier copies.
