# Assignment Plan and Process Log

This file tracks the take-home plan, decisions, prompts, AI workflow, verification, and submission readiness.

## Upfront Decisions

Answer these before final polish and submission:

1. Submission scope: ship all three implemented features, or intentionally present one as partial if you want the story to be more conservative?
2. Public repo destination: fork `getstreamline/folio-takehome`, create a fresh public repo, or push this branch to an existing repo?
3. Walkthrough video tool: Loom, unlisted YouTube, Google Drive link sharing, or another public link?
4. AI transparency level: include only a short process summary in the video, or also share this plan/process file in the repo?
5. Final polish budget: keep the current small-scope implementation, or spend extra time on edge cases such as editing document bodies, better schedule validation, or richer search?

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
- Set working branch: `takehome-features`.

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

Status: pending decisions

- Review UI copy for clarity and timezone expectations.
- Review diff for accidental overreach.
- Decide whether to commit this planning file.
- Optionally add a short README note if you want reviewers to see the design decisions without watching the full video first.

### Phase 6: Publish and Submit

Status: pending

- Push branch or public repo.
- Record walkthrough video.
- Verify public repo can be cloned without auth.
- Verify video is accessible without auth.
- Reply to the recruiting email with both links.

## Prompt and Process Log

Use this section to summarize the prompts and how AI was used. Keep it concise enough that it is useful in the video.

| Step | Prompt / Request Summary | AI Role | Human Judgment / Pushback | Outcome |
| --- | --- | --- | --- | --- |
| 1 | Find the job email that says "do this." | Searched Gmail, identified the assignment, summarized deliverables. | Confirmed the relevant email was the CivicPlus take-home, not unrelated messages. | Assignment repo and requirements identified. |
| 2 | Inspect the linked repo and README. | Cloned repo, read files, mapped app flow. | Chose to understand existing PHP/SQLite structure before coding. | Implementation plan formed around small helpers and existing page controllers. |
| 3 | Implement scoped take-home features. | Added migration runner, document helpers, UI flows, and recipient gating. | Chose to keep readable IDs separate from access control because readable IDs are guessable. | All three requested features implemented. |
| 4 | Verify behavior. | Ran Docker tests, PHP lint, and browser QA. | Caught timezone ambiguity during browser QA and added explicit timezone copy. | Tests passed and main user flow verified. |
| 5 | Plan completion and track process. | Created this file with phases, decisions, prompts, and submission checklist. | Pending owner decisions on repo destination, video tool, and final polish scope. | Ready for final polish and submission planning. |

## Verification Log

Latest known passing checks:

- `docker compose run --rm app php tests/test.php` -> 4 passed, 0 failed.
- `docker compose exec app php tests/test.php` -> 4 passed, 0 failed.
- `docker compose run --rm app sh -c "find . -name '*.php' -print0 | xargs -0 -n1 php -l"` -> no syntax errors.
- Browser QA: admin loads, scheduled document can be created, title search filters to the target document, share link is generated, future document is gated, schedule update unlocks the recipient view.

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

- [ ] Upfront decisions answered.
- [ ] Final diff reviewed.
- [ ] Planning/process file either committed or intentionally left out.
- [ ] Public repo/branch pushed.
- [ ] Video recorded and link-sharing verified.
- [ ] Email reply drafted with repo and video links.
