# Digital Library Management System

Lightweight, server-rendered library management software for physical books, designed for PHP 8.x, MySQL/MariaDB, and InfinityFree shared hosting.

## Status

Implemented foundation:

- Public catalog search, availability filtering, and pagination
- Registration, login, logout, password hashing, and secure sessions
- Role middleware for members, librarians, and administrators
- Category and author management with archive behavior
- Book management and physical-copy status management
- Borrowing, returns, due dates, and configurable overdue fines
- Reservation queues, cancellation, expiration, and ready-state promotion
- Profile updates with optional password changes
- Role-aware member and staff dashboard statistics
- Administrator audit-log viewing with pagination
- CSRF protection, output escaping, security headers, and production error handling
- PDO prepared statements and a normalized circulation schema
- Native modular autoloading with no runtime dependencies

Full workflow dashboards, broader audit coverage, and integration tests remain planned feature slices.

## Technology Stack

| Layer | Choice |
| --- | --- |
| Runtime | PHP 8.x with strict types |
| Database | MySQL 8+ or MariaDB with InnoDB |
| Data access | PDO, emulated prepares disabled |
| Server rendering | PHP templates and HTML5 |
| Styling | CSS3, responsive layout, no CSS framework |
| Interaction | Vanilla JavaScript only when needed |
| Icons | Allowlisted inline SVG helper, no icon dependency |
| Hosting | Apache-compatible InfinityFree shared hosting |
| Services | No Node.js, React, Vue, Laravel, Docker, Redis, WebSockets, workers, or daemons |

## Architecture

The application uses a small layered MVC-style structure without a framework:

```text
public/                         Web-accessible document root
  index.php                     Front controller and route dispatch
  assets/                       CSS and browser assets
app/
  autoload.php                  Native class loader for application modules
  bootstrap.php                 Configuration, sessions, headers, errors
  config/                       Environment and PDO configuration
  controllers/                  HTTP input and view orchestration
  helpers/                      Stateless shared helpers
  middleware/                   Authentication and authorization boundaries
  models/                       PDO repositories and query methods
  services/                     Business rules and validation
  views/                        Escaped server-rendered templates
database/
  001_schema.sql                Normalized tables, keys, and indexes
  002_seed.sql                  Initial roles and administrator seed
storage/
  logs/                         Internal logs; never public
  uploads/                      Validated covers; never executable
tests/                          Smoke and future integration tests
```

### Request flow

1. Apache rewrites a request to `public/index.php`.
2. `app/bootstrap.php` loads configuration, secure sessions, error handling, headers, and the native autoloader.
3. The front controller selects a route and constructs the required controller, service, and repository.
4. Controllers validate request intent and delegate business rules to services.
5. Repositories execute prepared PDO queries.
6. Views escape dynamic output with `e()` before rendering HTML.

Keep SQL out of views, business rules out of templates, and authorization in middleware or services rather than in JavaScript.

## Installation

### Requirements

- PHP 8.x with PDO MySQL, sessions, fileinfo, and standard password/hash extensions
- MySQL 8+ or MariaDB with InnoDB and foreign-key support
- Apache rewrite support for the included `.htaccess`

### Local setup

1. Copy `.env.example` to `.env`.
2. Set a unique database name, user, and password in `.env`.
3. Create the database and grant the application user only the required database privileges.
4. Run `database/001_schema.sql`.
5. Run `database/002_seed.sql`.
6. Set `APP_ENV=development` only for local work.
7. Start the server from the project root:

   ```sh
   php -S localhost:8000 -t public
   ```

8. Open `http://localhost:8000`.

The seed file contains the initial role records. Replace any seeded administrator password before production use if one is added to the seed process.

## Configuration

All supported values are listed in `.env.example`:

- `APP_ENV`: `development` or `production`
- `APP_URL`: canonical application URL
- `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`: database connection
- `SESSION_TIMEOUT`: inactivity timeout in seconds
- `MAX_ACTIVE_LOANS`: member loan limit
- `FINE_DAILY_RATE`, `FINE_GRACE_DAYS`, `FINE_MAX_AMOUNT`: configurable fine policy
- `UPLOAD_MAX_BYTES`: maximum cover upload size

Never commit `.env`, database credentials, private keys, or production logs. Production must disable display errors and use HTTPS.

## Security Guide

- Use PDO prepared statements for every dynamic value.
- Validate and normalize request input in services.
- Escape all output using `e()`.
- Require CSRF tokens for every state-changing form.
- Regenerate the session ID after successful authentication.
- Use `HttpOnly`, `SameSite=Lax`, and HTTPS-only cookies in HTTPS deployments.
- Enforce role and resource authorization on the server for every protected action.
- Use archive/status transitions when historical records depend on an entity.
- Store uploads outside executable paths, validate MIME and extension server-side, and generate filenames.
- Keep `.env`, `database`, `app`, and `storage` inaccessible from direct web requests where the host permits it.
- Review logs for operational failures without displaying stack traces to users.

## Database Guide

The schema separates a book title from its physical copies. Core relationships are:

- `roles` to `users`
- `categories` to `books`
- `books` to `book_authors` to `authors`
- `books` to `book_copies`
- `book_copies` to `loans`
- `books` to `reservations`
- `users` to loans, reservations, and audit records

Use transactions whenever an operation changes multiple records. Circulation must keep copy status and loan state consistent. Do not delete records referenced by historical loans or audit entries.

## SVG Icons

Icons are provided by `app/helpers/icons.php` through an allowlist of known icon names. The helper returns fixed inline SVG markup with `aria-hidden="true"`; it does not accept arbitrary SVG input. Use icons for familiar controls such as logout, search, navigation, and status actions, with visible text or an accessible label where the meaning is not obvious.

Do not paste untrusted SVG content into templates and do not use SVG as an upload format.

## Testing and Checks

Run PHP syntax checks from the project root:

```sh
find app public tests -name '*.php' -print0 | xargs -0 -n1 php -l
php tests/smoke.php
git diff --check
```

Before release, test authentication, authorization boundaries, CSRF, XSS, SQL injection, duplicate records, catalog search, transaction rollback, upload protections, error responses, and mobile/desktop layouts. PHP/database integration tests require PHP and a test MySQL/MariaDB database. Do not run destructive tests against production data.

## InfinityFree Deployment

The primary target is a single public root such as `htdocs`:

1. Create the MySQL database and user in the hosting control panel.
2. Upload the project contents to the public root.
3. Set production values in `.env` without committing the file.
4. Run the SQL files through the provider's database tool.
5. Keep the root `.htaccess` enabled.
6. Verify direct requests to `app/`, `database/`, and `storage/` are denied.
7. Ensure logs and uploads are writable only as required and cannot execute.
8. Enable HTTPS and use the HTTPS application URL.
9. Test login, catalog browsing, and one protected route after deployment.

If the account supports files outside the document root, move `app`, `database`, and `storage` above the public root and update the bootstrap path. Treat this as an optional stronger arrangement, not a hosting requirement.

## Git Workflow

Commit each completed feature independently:

```text
feat: add book management
security: harden file uploads
fix: prevent duplicate active loans
perf: optimize catalog queries
docs: update deployment guide
```

Before each commit, run diagnostics, PHP lint when available, the focused test, and `git diff --check`. Never commit `.env`, runtime logs, uploaded files, or database credentials.

## Roadmap

1. Complete workflow dashboards and reports
2. Broader audit coverage
3. Integration/security tests and final deployment review
