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
