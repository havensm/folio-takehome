# Assignment Plan and Process Log

This file tracks the take-home plan, decisions, prompts, AI workflow, verification, and submission readiness.

## Upfront Decisions

Resolved decisions:

1. Submission scope: ship all three requested features.
2. Public repo destination: `https://github.com/havensm/folio-takehome.git`.
3. AI transparency level: share the full process, from asking AI to pull the assignment email through implementation and verification.
4. Final polish budget: keep the implementation scoped, but make the scoped version bug-free and clearly call out future improvements.

Open before submission:

1. Walkthrough video tool: Loom, unlisted YouTube, Google Drive link sharing, or another public link?
2. Final email wording and exact links.

## Recommended Decisions

- Scope: ship all three features as completed for the exercise, while calling out production hardening as future work.
- Readable IDs: keep them human-facing but not security-sensitive; require the private share token for recipient access.
- Search: use case-insensitive title substring search because it is predictable, easy to explain, and enough for the small staff workflow.
- Migrations: keep `schema.sql` as the baseline and add numbered SQL files under `migrations/`, applied by a small runner during seeding.
- Video story: emphasize judgment, tradeoffs, and verification rather than claiming this is production-complete.

## Phases

### Phase 1: Assignment Intake and Constraints

Status: complete

- Read the assignment email and README.
- Identify hard requirements: migrations, tests per feature, audit logging, fresh-clone Docker flow.
- Identify ambiguous decisions: readable ID format, URL structure, token interaction, search behavior, scheduling semantics.
- Set working branch: `complete-assignment`.

### Phase 2: Codebase Recon

Status: complete

- Read schema, seed, bootstrap helpers, page controllers, layout, CSS, and tests.
- Map current flow: admin creates documents, share page creates token links, view page resolves token to document.
- Identify natural extension points: `lib/` helpers, admin form/list, share URL generation, view access check, test harness.

### Phase 3: Implementation

Status: complete

- Add `lib/migrations.php` and `migrations/001_document_publishing_and_readable_ids.sql`.
- Add `lib/documents.php` for readable IDs, schedule parsing, publication checks, search, document creation, and share creation.
- Extend admin creation with optional publish time.
- Add inline schedule editing to the document list.
- Add title search to the document list before sharing.
- Include readable IDs in admin/share/recipient screens.
- Keep token-based share access and add readable ID as a URL hint.
- Gate recipient view until `published_at`.
- Log document creation, schedule changes, and share creation.

### Phase 4: Tests and QA

Status: complete

- Add tests for readable IDs, scheduled publishing, and title search.
- Run containerized tests.
- Run PHP syntax checks.
- Use browser QA to exercise create scheduled doc, search, share, gated view, schedule update, and visible recipient view.
- Capture any surprises for the video.

### Phase 5: Final Polish

Status: complete

- Review UI copy for clarity and timezone expectations.
- Review diff for accidental overreach.
- Keep this planning file committed for transparency.
- Use independent review passes to look for scoped-version bugs before publishing.
- Add a short README submission note so reviewers can see the design decisions without watching the full video first.
- Fix scoped review findings around migration backfill, invalid date handling, and literal title search.

### Phase 6: Publish and Submit

Status: pending branch push and video

- Push implementation branch to `havensm/folio-takehome`.
- Record walkthrough video.
- Verify public repo can be cloned without auth.
- Verify video is accessible without auth.
- Reply to the recruiting email with both links.

## Prompt and Process Log

Use this section to summarize the prompts and how AI was used. Keep it concise enough that it is useful in the video.

| Step | Prompt / Request Summary | AI Role | Human Judgment / Pushback | Outcome |
| --- | --- | --- | --- | --- |
| 1 | Find the job email that says "do this." | Used the user-requested Gmail connector to identify the assignment and summarize deliverables. | Confirmed the relevant email was the CivicPlus take-home, not unrelated messages. | Assignment repo and requirements identified. |
| 2 | Inspect the linked repo and README. | Cloned repo, read files, mapped app flow. | Chose to understand existing PHP/SQLite structure before coding. | Implementation plan formed around small helpers and existing page controllers. |
| 3 | Implement scoped take-home features. | Added migration runner, document helpers, UI flows, and recipient gating. | Chose to keep readable IDs separate from access control because readable IDs are guessable. | All three requested features implemented. |
| 4 | Verify behavior. | Ran Docker tests, PHP lint, and browser QA. | Caught timezone ambiguity during browser QA and added explicit timezone copy. | Tests passed and main user flow verified. |
| 5 | Plan completion and track process. | Created this file with phases, decisions, prompts, and submission checklist. | Decided to share the process file publicly as part of full transparency. | Process log became a committed repo artifact. |
| 6 | Initialize the public repo. | Suggested repo name/description and pushed a small initialization commit. | Chose `havensm/folio-takehome` as the public destination. | Public repo initialized with the baseline and process plan. |
| 7 | Complete implementation in segments and use sub-agents as needed. | Applied implementation in reviewable segments and spawned independent code/process review agents. | Kept scope tight: all three features, no broad product expansion. | Review findings were used to harden scoped behavior and docs. |

## Verification Log

Latest known passing checks:

- `docker compose run --rm app php tests/test.php` -> 6 passed, 0 failed.
- `docker compose exec app php tests/test.php` -> 6 passed, 0 failed.
- `docker compose run --rm app sh -c "find . -name '*.php' -print0 | xargs -0 -n1 php -l"` -> no syntax errors.
- Browser QA: admin loads, scheduled document can be created, title search filters to the target document, share link is generated, future document is gated, schedule update unlocks the recipient view.
- In-app Browser fill hit a local virtual-clipboard runtime issue, so rendered flow verification used Playwright fallback. The expected 403 for a future-scheduled recipient link was observed during the gated-view check.

## Video Outline

1. Context: Folio lets staff create documents and share one-time links.
2. What changed: scheduled publishing, readable IDs, share-by-title search.
3. Decisions:
   - Readable IDs complement tokens rather than replace them.
   - Substring title search is predictable and sufficient for this small workflow.
   - Migrations are numbered SQL files applied during seed.
4. Code tour:
   - `migrations/001_document_publishing_and_readable_ids.sql`
   - `lib/migrations.php`
   - `lib/documents.php`
   - `public/admin.php`
   - `public/share.php`
   - `public/view.php`
   - `tests/test.php`
5. Verification: tests, lint, browser flow.
6. Existing-code observations:
   - No real auth; `current_staff()` always returns staff row 1.
   - Seeding resets the database, which is fine for the exercise but not a real migration lifecycle.
   - Timezone handling matters for scheduled publishing.
7. With more time:
   - Add server-side validation around schedule windows.
   - Add pagination or ranking for search once document volume grows.
   - Add a real migration command separate from seeding.
   - Add integration-style HTTP tests for recipient views.
8. AI workflow:
   - Used AI to inspect, implement, and verify quickly.
   - Human decisions focused on security tradeoffs, scope control, and QA interpretation.
   - Pushed back on an implicit timezone assumption after browser testing showed confusing behavior.

## Submission Checklist

- [x] Upfront decisions answered.
- [ ] Final diff reviewed.
- [x] Planning/process file included for transparency.
- [ ] Public repo/branch pushed.
- [ ] Video recorded and link-sharing verified.
- [ ] Email reply drafted with repo and video links.
