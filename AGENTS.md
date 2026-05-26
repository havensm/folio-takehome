# Agent Notes

- Run the app with `docker compose up`; it reseeds `db.sqlite` on each start.
- Run tests with `docker compose exec app php tests/test.php` against a running container, or `docker compose run --rm app php tests/test.php`.
- Put schema changes in numbered files under `migrations/`; keep `schema.sql` as the original baseline.
- Treat readable document IDs as human-facing labels, not access control. Recipient access still requires a share token.
- Keep feature tests focused in `tests/test.php` and prefer small helpers in `lib/` over duplicating SQL in page controllers.
