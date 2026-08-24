# Digital Library Management System

Server-rendered PHP 8.x application for physical library circulation, designed for MySQL/MariaDB and InfinityFree shared hosting.

## Current status

Foundation is in place: repository layout, configuration loading, PDO bootstrap, initial schema, protected upload storage, security headers, and a public shell. Authentication and catalog workflows are next.

## Local setup

1. Copy `.env.example` to `.env` and set database credentials.
2. Create the database, then run `database/001_schema.sql` and `database/002_seed.sql`.
3. Start locally with `php -S localhost:8000 -t public`.
4. Open `http://localhost:8000`.

Never commit `.env`. For InfinityFree, upload the project into the account's public root and keep `.htaccess` protections in place. If the host permits directories outside the public root, move `app`, `database`, and `storage` there and adjust the bootstrap path.

## Checks

Run `php -l` on each PHP file. Database integration tests will be added with the feature slices.
