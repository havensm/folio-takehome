# Folio

Folio is a small internal document-sharing app. Staff can create documents, schedule when recipients can see them, search for documents by title, and generate private recipient share links.

The app is intentionally lightweight: PHP pages, SQLite storage, and Docker Compose for a repeatable local environment.

## Features

- Create staff-authored documents from the admin screen.
- Publish documents immediately or schedule them for a later date and time.
- Generate short, readable document IDs for staff context and share URLs.
- Keep recipient access private with opaque share tokens.
- Search document titles before creating a share link.
- Gate recipient views until the scheduled publish time.
- Log document creation, schedule changes, and share creation in `audit_log`.

## Tech Stack

- PHP with the built-in development server
- SQLite
- Docker and Docker Compose
- Plain server-rendered HTML/CSS

## Requirements

- Docker Desktop or another Docker environment with Compose support

No local PHP or SQLite installation is required.

## Quick Start

Start the app:

```sh
docker compose up
```

Open the staff admin:

```text
http://localhost:8000/admin.php
```

The first run builds the image. Each `docker compose up` run recreates `db.sqlite` from the schema, migrations, and seed data so the local app starts from a known state.

Stop the app with `Ctrl+C`.

## Seed Data

`seed.php` creates one staff user and one sample document/share link. The Docker Compose startup output includes the sample recipient link.

The seeded staff user is:

```text
Freddy Folio <freddy@folio.example>
```

## Running Tests

With the app container already running:

```sh
docker compose exec app php tests/test.php
```

Or run the tests in a one-off container:

```sh
docker compose run --rm app php tests/test.php
```

Optional PHP syntax check:

```sh
docker compose run --rm app sh -c "find . -name '*.php' -print0 | xargs -0 -n1 php -l"
```

## App Workflow

1. Open `/admin.php`.
2. Create a document with a title, body, and optional publish time.
3. Use the document table to search by title, update availability, review the document, or create a share link.
4. Send the generated `/view.php?id=...&token=...` link to the recipient.
5. Recipients see a not-yet-available message until the document reaches its publish time.

Readable document IDs are human-facing labels, not access control. Recipient links still require the private share token.

## Project Layout

```text
public/              Web entry points and assets
lib/                 Bootstrap, layout, document helpers, migrations
migrations/          Numbered SQL migrations applied during seeding
tests/test.php       Lightweight feature test runner
schema.sql           Baseline schema
seed.php             Local database reset and seed script
docker-compose.yml   Local app runtime
```

## Assignment Notes

The original take-home assignment README has been moved to `ASSIGNMENT_NOTES.md`.

Implementation planning, verification notes, and AI-process notes are in `ASSIGNMENT_PLAN.md`.

Repo-specific agent conventions are in `AGENTS.md`.

## Get Started With Codex

Copy and paste this prompt into a local Codex instance to clone the project, start it locally, and verify the setup:

```text
Clone and run the Folio app locally from https://github.com/havensm/folio-takehome.

Please:
1. Clone the repo into a local workspace if it is not already present.
2. Read README.md, AGENTS.md, ASSIGNMENT_NOTES.md, and ASSIGNMENT_PLAN.md for project context.
3. Verify Docker Compose is available.
4. Start the app with docker compose up. Use another local port only if 8000 is already busy.
5. Run docker compose run --rm app php tests/test.php.
6. Report the local app URL, the seeded sample share URL from startup output, the test result, and any setup issues.

Keep setup or code changes on a new branch, and do not modify files unless I ask.
```
