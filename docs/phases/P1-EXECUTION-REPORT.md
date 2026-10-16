# P1 Execution Report

**Phase:** P1 — Foundation and Admin Shell. **Plan:** `docs/phases/P1.md`. **Date:** 2026-10-09.
**Overall status:** IMPLEMENTATION COMPLETE — acceptance PENDING owner review (see gate audit).

## Gate audit (P1.md §16)

| Gate item | Verdict | Evidence |
|---|---|---|
| Fresh install → secure usable admin shell | PASS | Zero-state DB run: `migrate:fresh --seed` + `app:create-admin` + real-HTTP login → dashboard 200 (T02.7); documented steps in README + command help |
| Auth/session hardening verified | PASS | T02.1–T02.6: invite/throttle/2FA/timeout/invalidation/cookies, 7+7+4+7+7 focused tests green |
| Permission matrix passes | PASS | Generated 4×29 matrix + guests + idempotence; 212 assertions green |
| Settings typed/validated/authorized/cached | PASS | 9 groups, rules enforced in UI, invalidation tested |
| Audit recorded/masked/authorized/retained | PASS | 12 audit tests incl. tamper/prune/failure/CSV/export gates |
| Concurrency prevents silent lost updates | PASS | Compare-and-swap + conflict tests (competing/stale/rollback) |
| Representative CRUD reuses patterns | PASS | Users/Roles UI, 10/10 tests, no bespoke table/form logic |
| Locale/timezone verified | PASS | UTC storage, conversion, DST, frozen clock, parsing, locale defaults |
| Notifications scoped, email-off-default | PASS | 9/9 tests (23 assertions) incl. sync retry-dedup, per-user scoping, fail-fast contract key |
| Gates green, no new test deps | PASS | `composer test` full green: Pint, PHPStan L7, 156 tests (154 pass, 2 self-skips, 1 pre-existing risky); `composer audit` + `npm audit` clean; `npm run build` green |
| Docs + traceability complete | PASS | P1.md §15 rows map to completed subtasks; plan corrections recorded |
| E-P0-01 before auth production-ready | CHECKPOINT OPEN | Partial evidence (T02.7); clean-checkout demo still needs a remote |
| E-P0-02 hosted run before prod readiness | CHECKPOINT OPEN | Workflow ready, audits green locally; no remote/CI access here |
| No P2 leakage | PASS | Diff review: no articles/taxonomy/media-UI/editor/themes/search/modules; no custom framework; no unapproved deps |

**Pre-existing items preserved:** empty `SecurityTest` placeholder (risky), Fortify `registration()` removal
rationale, `verified` removal rationale, DES-1/M4 open, Q-P1-01 default (matrix as written) — all recorded above.
**Scope note:** every P1 implementation change is recorded in this report's subtask log; nothing outside it.
Outstanding: owner review (§17), E-P0-01/E-P0-02 checkpoints.

### First-admin password handling review (§4 remediation)

- Interactive prompt is the default; `--password` is automation-only with a shell-history warning now in the
  command help text. The command echoes only email/role, never the password; tests assert outcomes, not secrets.
- Verified: piped/non-TTY stdin fails cleanly via `NonInteractiveValidationException` before any write
  (no partial users); full TTY interaction cannot be exercised in this shell — recorded limitation.

### Audit immutability scope (§3 remediation)

- Protected: Eloquent `updating`/`deleting` on Activity throw (tested); no update/delete/export-mutation
  endpoints exist; retention uses query-builder deletion (`Audit::prune`, package `CleanActivityLogAction`)
  plus the scheduled daily `activitylog:clean` (registered — `schedule:list` verified).
- Limitation (recorded, not expanded): application-level only. Direct database access bypasses the guards;
  no DB triggers or RLS introduced (no requirement mandates them).

## Baseline (pre-execution, verified this turn)

- `composer test`: Pint ✓, PHPStan L7 ✓, 31 tests / 30 passed / 1 skipped (2FA off) / 1 risky (pre-existing placeholder). No pre-existing failures.
- Git: `main` with P0 commits; working tree holds the formal-closure doc edits (uncommitted, preserved) + untracked `docs/phases/P1.md`.
- Test DB: system MariaDB, `newscms_test` (guard in `TestCase::setUpTraits()` verified prior turn).
- No Spatie packages installed. Fortify: registration + resetPasswords. Admin locale: English-only (no enforcement point yet — recorded in T07.1).

## Subtask log

### P1-T01.1 Spatie Permission install — COMPLETE

- `composer require spatie/laravel-permission:^8.3` → 8.3.0 (PHP ^8.3 ✓, illuminate ^12|^13 ✓, MIT ✓).
  `composer audit` clean. Published migration + config (`events_enabled => true` for cache invalidation).
  Side effect (sanctioned post-update-cmd): `boost:update` appended the guidelines block to `AGENTS.md`.
- Verification: version record + audit + migrations apply on `newscms_test`.

### P1-T01.3 Users delta + single-role model — COMPLETE

- Files: migration `add_p1_fields_to_users_table` (`is_active` default true, nullable `locale`/
  `last_login_at`, reversible), `User` (`HasRoles`, `assignRole`/`syncRoles` exactly-one enforcement with
  replace semantics, casts, Fillable unchanged — attributes set explicitly).
- Incident (found by hung test, fixed at cause): spatie's `syncRoles()` delegates back to `assignRole()`,
  so overriding both naively recursed infinitely. Fixed with dual trait aliases + detach-then-attach in
  `assignRole`; verified assign + replace paths.
- Verification: Pint clean; PHPStan L7 clean (precise variadic generics per parent contract); migration
  up on `newscms_test`.

### P1-T01.2 Catalog + gates + cache — COMPLETE

- Files: `app/Support/Permissions.php` (29 PRD §4.2 keys verbatim; role defaults incl. Reporter
  `tags.manage` create-only per Q-P1-01 matrix-as-written; Redis-shape `role:{id}:perms` cache on default
  store; additive-safe flush), `app/Providers/AuthServiceProvider.php` (29 Gate abilities, fail-closed;
  invalidation via Spatie attach/detach domain events + saved/deleted — Eloquent has no pivot model events
  in this Laravel version, verified in vendor source), registered in `bootstrap/providers.php`.
- Verification: Pint clean; PHPStan L7 clean (contract-vs-concrete fixed via guarded query; template-type
  narrowing trusted); catalog/gate/invalidation/invariant tests green.

### P1-T01.4 Additive role seeding — COMPLETE

- Files: `database/seeders/RoleSeeder.php` (firstOrCreate roles + permissions; attach-missing-only; flush),
  `DatabaseSeeder` calls it. Seeded: 4 roles, 29 permissions, 64 pivots (29+22+7+6).
- Verification: automated idempotence test (revoke + custom grant survive re-seed); manual probe matched.
- Incident: first manual `db:seed` ran on default connection (dev DB) and failed cleanly on missing tables
  (no writes); dev DB verified untouched, then polluted by an explicit run and restored (tables dropped,
  migration rows removed, user row intact). Seeding now always targets `newscms_test` explicitly outside tests.

### P1-T01.6 Enforcement + UI gating + P2 registration doc — COMPLETE

- Files: `routes/web.php` (`system.status` gated by `can:system.view`), `tests/Feature/SystemStatusTest.php`
  (Admin 200, role-less/Reporter 403, guest redirect), P1.md §8 P2 registration contract.
- Policies: none writable yet (no P2 models); `User` authorization for the Users UI lands in P1-T06.6.
- Verification: 4/4 pass (36 assertions).
- Plan correction: catalog holds 29 keys, not 28 (test-verified count; P1.md §8 fixed).

### P1-T01.5 Generated matrix test — COMPLETE

- Files: `tests/Feature/PermissionMatrixTest.php` (4 roles × 29 keys generated assertions + guest denial).
- Verification: 8/8 permission tests pass (212 assertions) after the recursion fix.
- Note: inactive-user matrix cases ride P1-T02.6 (`EnsureUserActive`); recorded, not skipped silently.

### Dependency reviews (P1-T01.1 / P1-T03.1 / P1-T04.1) — COMPLETE, all three installs owner-APPROVED

| Package | Selected | PHP / Laravel | License | Maintenance | Security |
|---|---|---|---|---|---|
| `spatie/laravel-permission` 8.3.0 | `composer require spatie/laravel-permission:^8.3` | PHP ^8.3 ✓ / illuminate ^12\|^13 ✓ | MIT ✓ | Current major with L13 support ✓ | No advisories checkable pre-install; `composer audit` post-install |
| `spatie/laravel-settings` 3.9.0 | `composer require spatie/laravel-settings:^3.9` | PHP ^8.2 ✓ / illuminate ^11\|^12\|^13 ✓ | MIT ✓ | Current major ✓ | Same |
| `spatie/laravel-activitylog` 5.1.1 | `composer require spatie/laravel-activitylog:^5.1` | PHP ^8.4 ✓ / illuminate ^12\|\|^13 ✓ | MIT ✓ | Current major ✓ | Same |

- Alternatives: custom tables — rejected by ADR-0001…0004 and TRD §2. No other runtime dependency
  anticipated. Native Laravel/Livewire/Blade/Flux insufficient because: RBAC catalog + role assignment +
  permission caching (permission), typed validated settings store (settings), immutable activity store +
  tooling (activitylog) — all commodity problems the ADRs assigned to these packages.

### P1-T07.1 Time foundations — COMPLETE

- Files: `config/app.php` (`site_timezone`, default `Asia/Kolkata`), `app/Support/SiteTime.php` (UTC storage,
  display-only conversion), `tests/Unit/SiteTimeTest.php` (conversion/DST-boundary/frozen-clock),
  `tests/Feature/TimeBehaviorTest.php` (UTC default, site-tz wiring, timestamp round-trip).
- Verification: new tests 5/5 pass; Pint clean; PHPStan L7 clean.
- Note: route-level admin-locale enforcement deferred to the admin group (P1-T02.2); rationale recorded,
  acceptance preserved (config + helper + tests delivered).

### P1-T06.1 Layout/navigation/header patterns — COMPLETE

- Files: `app/Support/AdminNav.php` (code-defined registry; renders only registered routes; optional
  `permission` key evaluated via Gate, wired in P1-T01.6), sidebar renders from registry,
  `resources/views/components/ui/page-header.blade.php` + `ui/breadcrumbs.blade.php`,
  `tests/Feature/AdminShellTest.php` (5 tests: registry shape/route-existence, sidebar content,
  header, breadcrumbs, empty state).
- Verification: 5/5 pass (16 assertions); Pint clean; PHPStan L7 clean (test needed `(string)` cast
  on `$this->blade()` for `toContain`).
- Note: permission-key wiring deferred to P1-T01.6 by plan; registry already filters unregistered routes.

### P1-T06.3 Form pattern — COMPLETE (collectively with P1-T06.6)

- Files: `resources/views/components/ui/form-errors.blade.php` (assertive summary list),
  `tests/Feature/FormPatternTest.php` (2 tests); interactive proof in Users create/edit forms.
- Evidence mapping: validation + server errors (rejected-input tests + `flux:error` per field); save +
  accurate feedback (redirect + `x-ui/flash-status` text asserted); recovery without input loss (failed
  post → form re-renders old input + `role="alert"` summary, tested); labels/help/errors on every input;
  dirty-state wiring present on both forms (markup asserted; Alpine behavior itself untestable here);
  server-side validation authoritative (controller `validate()`); patterns shared, not duplicated.
- Focus: no custom focus shift — native browser behavior preserved, errors announced via `role="alert"`.
- Verification: form + Users suites green (12/12 UsersManagement).

### P1-T06.4 Field/dialog/feedback components — COMPLETE

- Files: `x-ui/confirm-dialog`, `x-ui/language-tabs`, `x-ui/datetime-field` (site-tz hint via T07.1),
  `x-ui/slug-field`, `x-ui/code-editor` (display-only); `tests/Feature/FieldComponentsTest.php` (6 tests).
- Decisions: no custom badge (Flux `flux:badge` used directly + text+dot rule); no custom date picker
  (Flux has none — native `datetime-local` + tz hint); toasts use the persisted Flux group (no wrapper).
- Verification: 6/6 pass (18 assertions) — render tests prove every Flux API used.

### P1-T03.1 Spatie Settings install — COMPLETE

- `composer require spatie/laravel-settings:^3.9` → 3.9.0 (PHP ^8.2 ✓, illuminate ^11|^12|^13 ✓, MIT ✓).
  `composer audit` clean. Published migration + config (Data-cast line commented: needs uninstalled
  `spatie/laravel-data`).
- Verification: version record + audit + migration applies.

### P1-T03.2 Typed settings classes — COMPLETE

- Files: `app/Settings/NewsSettings.php` (owner()/rules() conventions) + 9 group classes covering every
  PRD §15.6 minimum field with type/default/rule/owner; `database/settings/*_seed_p1_settings.php`.
- Key discovery (fixed at cause): the package treats class-default-backed properties as missing on save —
  first save requires seeded DB rows. Sanctioned path used: settings migration seeding all defaults
  (required for fresh installs, not just tests).
- Verification: Pint + PHPStan clean; defaults/rules/owners/persistence tests green.

### P1-T04.1 Spatie Activitylog install — COMPLETE

- `composer require spatie/laravel-activitylog:^5.1` → 5.1.1 (PHP ^8.4 ✓, illuminate ^12||^13 ✓, MIT ✓).
  `composer audit` clean. Published migration + config (`clean_after_days` from `AUDIT_RETENTION_DAYS`).
- Verification: version record + audit + migration applies.

### P1-T04.2 Event catalog + producers + masking — COMPLETE

- Files: `app/Support/Audit.php` (log/mask/prune + loud-failure path), `app/Observers/UserObserver.php`
  (created/updated with masked changes), `app/Providers/AuditServiceProvider.php` (auth/role/settings
  listeners, append-only guards).
- Findings fixed at cause: `getExtraProperty` does not exist in v5 (correct API `getProperty`); PHPStan
  contract-vs-concrete and always-true narrowing; `Failed::$credentials` trusted per its PHPDoc.
- Verification: Pint + PHPStan clean; event/masking/role-audit tests green.

### P1-T04.3 Admin viewer — COMPLETE

- Files: `AuditLogController` (filters/pagination/CSV export capped by chunking), `audit-log.blade.php`
  (Flux table + `x-ui` patterns), routes gated by `can:audit.view`.
- Verification: auth/filter/CSV/denied tests green.

### P1-T04.4 Immutability + retention + failure behavior — COMPLETE

- Model `updating`/`deleting` guards throw; pruning bypasses via query builder (`Audit::prune` +
  scheduled `activitylog:clean --force` daily); failure path logs critical and continues.
- Verification: tamper/prune/failure tests green.

### P1-T03.5 Audit integration — COMPLETE

- `SettingsSaved` listener records group-only events (never values); test asserts group present + values
  absent. Missing import caught by PHPStan, fixed.

### P1-T08 Notifications infrastructure — COMPLETE

- Files: migration `create_notifications_table` (Laravel standard) + `add_email_notifications_to_users`;
  `NotificationsController` (index with bell data, preference toggle, per-item + mark-all read with
  ownership 403); `notifications/index.blade.php`; routes (auth; no permission gate — every staff member
  has notifications); `app/Support/Notifications.php` (`send` + `sendOnce`); `tests/Feature/NotificationsTest.php`
  (9 tests, in-file test event).
- Deduplication, as implemented and enforced in code (`app/Support/Notifications.php::sendOnce`): the same
  logical notification is the same user + same notification class + same `data->dedupe_key` with an
  unread row (`read_at` NULL). The check is application-level check-then-insert against the standard
  `notifications` table (no unique index added); missing keys fail fast with `InvalidArgumentException`
  (verified: base `Notification` has no `toArray`, so the check probes with `method_exists` exactly like
  the database channel). Proven by tests: sequential redelivery → 1 row; distinct keys → 2 rows; read
  rows re-notify; keys scoped per user; missing key → throws with zero writes.
- Retry behavior verified: synchronous sender redelivery (the sender calls `sendOnce()` twice) → single
  row. Not covered: `ShouldQueue` notifications under an async worker — the check runs before queueing,
  so two `sendOnce()` calls made before the worker delivers can each queue a job (duplicates after
  delivery); retries of the queued `SendQueuedNotifications` job itself bypass `sendOnce`. Concurrent
  same-instant sends can both pass the check (no distributed lock, no unique index) — documented in code.
- Partial failure follows each notification's own `via()` channel order (the P1 test event is mail-first):
  a mail failure leaves no database row, so a sender retry re-attempts both channels (no loss); a database
  failure after mail was sent can duplicate the email on retry. Email stays optional per user
  (`email_notifications`, default false); database delivery is unconditional.
- Enforced vs delegated: the code enforces dedup only for emitters that call `sendOnce()` with a stable
  key present in `toArray()` (e.g. `article:42:submitted`); nothing in code stops a future emitter from
  calling `send()` directly and bypassing dedup. P2 emitters must opt in per event — P2 discipline, not a
  P1 lock.
- Contracts for P2 emitters: `via()` reads `email_notifications` (off default); send database always.
- Verification: 9/9 pass (23 assertions); Pint + PHPStan L7 clean.

### P1-T06.6 Users & Roles management UI (representative CRUD) — COMPLETE

- Files: `UsersController` (index with search/role/status filters, sorting, session-persisted page size,
  bulk deactivate; create with temp password; edit with role/locale/active; deactivate/activate with
  self + last-admin guards), `RolesController@index` (counts), 4 Blade screens reusing every T06 pattern
  (toolbar/table/form-errors/dialogs/badges/empty states/dirty-warning/flash), `x-ui/flash-status` in the
  shared layout, `tests/Feature/UsersManagementTest.php` (10 tests).
- Verification: 10/10 pass (40 assertions); Pint + PHPStan clean. Form patterns (save/flash/dirty/errors)
  now proven here, closing the T06.3 split.

### P1-T06.8 A11y/responsive/design-token conformance — COMPLETE (manual pass, limits disclosed)

- Logical-property lint: zero `ml-/mr-/left-/right-` violations in project views; landmarks (`nav`,
  `alert`, `status`) present; labels on all inputs; keyboard/focus/contrast inherited from Flux primitives.
- Limits: no browser automation in this environment — pass is markup review + render tests, not assistive-
  technology verification. No brand values invented (DES-1 open).

### Route-shadowing incident (found by full-suite regression, fixed at cause)

- `admin/settings/{group}` (T03.3) was registered before the starter-kit Livewire settings pages and
  swallowed them: plain users got 403 (the `can:settings.manage` gate on the shadow route), admins got 404
  (`resolveGroup` abort). Misleading trail included a real `Authorize::handle` frame (the shadow route's
genuine gate) and a `NotFoundHttpException` (the abort). Fixed with a `group/` path infix plus a `whereIn`
  backstop derived from `SettingsController::groups()` (single source, no duplication).
- Full suite green after the fix, and again after remediation: 155 tests / 153 passed / 2 self-skips / 1 pre-existing risky.

### P1-T03.3 Grouped settings UI — COMPLETE

- Files: `SettingsController` (group map, field maps, validation via class rules(), full-write merge,
  special handling for toggles/profiles-JSON/languages-CSV), `settings/index.blade.php` +
  `settings/edit.blade.php` (dynamic forms over Flux + `x-ui` patterns), routes gated by
  `can:settings.manage`, `tests/Feature/SettingsUiTest.php` (6 tests).
- Verification: 6/6 pass (16 assertions); Pint + PHPStan clean.

### P1-T03.4 Cache invalidation + edge behavior — COMPLETE

- Covered with T03.2: stale-read (write→fresh-read), invalid (rules rejection), missing (fresh defaults),
  language-scoped persistence. Full-write semantics documented for T03.3 forms (submit all group fields).
- Verification: green within the settings suites.

### P1-T06.5 Media-picker boundary — COMPLETE

- Files: `x-ui/media-picker` (trigger + preview slots; P2 selection contract in component comment:
  `media-picker:open` browser event → `media-picker:selected` Livewire event with `{id, thumb_url, alt}`),
  `tests/Feature/MediaPickerTest.php` (1 test).
- Verification: 1/1 pass (3 assertions).

### P1-T06.7 Dashboard + System status — COMPLETE

- Files: `app/Support/SystemStatus.php` (live probes: versions, DB, migrations, failed jobs, Redis, disk),
  `app/Http/Controllers/SystemStatusController.php`, `resources/views/system-status.blade.php` (Flux table),
  route `system.status` gated by `can:system.view` (P1-T01.6), `resources/views/dashboard.blade.php`
  (registry-driven entry cards, no placeholders), `tests/Feature/SystemStatusTest.php`.
- Verification: status 4/4 + dashboard entries 1/1 pass; Pint + PHPStan clean (4 genuine findings fixed at
  cause: missing shape type, meaningless null-probe replaced with real query, int/float strict comparison).
- Dashboard: permission-aware entry cards from `AdminNav::visibleItems()` (self excluded); no analytics,
  no dead links. AdminNav descriptions added for all entries.

### P1-T05.1 revisions table + RevisionStore service — COMPLETE

- Files: migration `2026_10_09_124701_create_revisions_table.php` (PRD §20 shape: morph subject,
  nullable user FK, kind enum, json snapshot, created_at only), `app/Models/Revision.php` (@property types,
  array cast), `app/Support/RevisionStore.php` (record/restore/prune), `tests/Feature/RevisionTest.php`
  (in-test fixture table, 4 tests).
- Design correction vs plan: service class instead of `HasRevisions` trait. Cause: PHPStan analyses traits
  only through using classes, and the first model consumer arrives in P2 — a trait would ship unanalysed
  (`trait.unused`, verified against the identifier docs). Contract preserved; P1.md T05.1 updated.
- Verification: Pint clean; PHPStan L7 clean; 4/4 tests pass.

### P1-T05.2 Compare-and-swap + conflict path — COMPLETE

- Files: `app/Exceptions/ConflictException.php` (`Changed by {user} at {time}` message, safe values only),
  `app/Support/CompareAndSwap.php` (single atomic UPDATE constrained by expected version; no transaction
  needed), `tests/Feature/CompareAndSwapTest.php` (in-test fixture table, 3 tests: success+bump,
  stale-conflict with message + rollback intact, concurrent writers).
- Verification: Pint clean; PHPStan L7 clean (two genuine findings fixed at cause: dead null-coalescing on
  `refresh()`, magic-property access replaced with `getAttribute()`); 3/3 tests pass.

### P1-T05.3 P2 integration contract — COMPLETE

- P2 `SaveArticle` consumes `RevisionStore::record/restore/prune` + `CompareAndSwap::attempt`:
  autosave writes `autosave` revisions only (never the article row); explicit save/publish writes a `manual`
  revision then compare-and-swaps the article row; zero matched rows → `ConflictException` → conflict dialog
  fed by the exception message; restore creates a new `manual` revision first (reversible). No edit locks
  anywhere (D13 holds). Authorization stays with callers; the services perform no auth.

### P1-T07.2 Language plumbing — COMPLETE

- Files: `config/app.php` (`site_languages` via `SiteTime::parseLanguages`), `SiteTime::parseLanguages`
  (trim/shape-validate/fallback-`en`; interface-vs-content separation documented in docblock),
  `tests/Unit/SiteTimeTest.php` (+parse cases), `tests/Feature/TimeBehaviorTest.php` (+locale/site-language tests).
- String audit: new P1 PHP files contain no Blade UI strings (SiteTime pure; seeder strings console-only).
- Verification: focused tests 7/7 pass (18 assertions); Pint clean; PHPStan L7 clean (redundant
  `array_values` removed at cause; config import style auto-fixed by Pint).

### P1-T02.2 Configurable ADMIN_PATH + admin prefix — COMPLETE

- Files: `config/app.php` (`admin_path`, default `admin`), `routes/web.php` + `routes/settings.php`
  (admin groups under prefix), `AppServiceProvider::configureLivewireUpdateRoute` (S3 pattern, permanent),
  welcome route stripped of session/CSRF/error middleware (cookie-free public page; P3 `cms.public` precursor),
  `.env.example` (`ADMIN_PATH=admin`, `APP_NAME=NewsCMS`), `tests/Feature/AdminPrefixTest.php` (4 tests).
- Finding (fixed at cause): Laravel 13's CSRF middleware is `PreventRequestForgery` — the legacy
  `VerifyCsrfToken` class name does not resolve, so the first `withoutMiddleware` missed it (500 via
  `Request->session()`). Corrected class named.
- Verification: 4/4 pass; Pint + PHPStan clean.

### P1-T02.5 Optional TOTP 2FA + FORCE_ADMIN_2FA — COMPLETE

- Files: Fortify 2FA feature on; `TwoFactorAuthenticatable` on User; challenge Blade view;
  2FA management UI in security settings (enable/QR/codes/regenerate/disable via Fortify actions);
  `EnsureTwoFactorAuthentication` middleware (alias `ensure-2fa` on admin groups; setup/challenge/logout
  excluded); `force_admin_2fa` config + `.env.example`; `LoginResponse` + `TwoFactorLoginResponse`
  overrides redirect to `route('dashboard')` (Fortify static `home` cannot follow ADMIN_PATH);
  `tests/Feature/Auth/TwoFactorTest.php` (7 tests: challenge, TOTP, recovery+consume, enable/disable,
  force-on/off/non-admin cases).
- Findings fixed at cause: `xss-ok` marker + CI grep refinement for first-party QR SVG (gate stays strict);
  challenge success uses `TwoFactorLoginResponse`, not `LoginResponse`; login view's dead register link
  replaced (it 500'd); welcome view already guarded by `Route::has`.
- SecurityTest render test now asserts the enabled 2FA section (updated to intended behavior, not weakened).
- Verification: 7/7 new + starter-kit auth/security suites green (previously-skipped 2FA tests now run).

### P1-T02.6 Session hardening — COMPLETE

- Files: `EnsureUserActive` middleware (alias `ensure-active` on admin groups; logout + session deletion +
  redirect), `Session` read model + `User::sessions()`, `force_admin_2fa`/`ADMIN_IDLE_TIMEOUT_MIN` config +
  `.env.example`, session cookie defaults (`cms_session`, path follows `ADMIN_PATH`), login tracking
  (`last_login_at` listener), logout-other-sessions UI + action, `tests/Feature/Auth/SessionHardeningTest.php`.
- Verification: 7/7 pass — scoped cookie, flags, idle default + expiry, inactive denial + session deletion,
  last_login_at, rotation, logout-others. Pint + PHPStan clean.
- Deep finding: same-process tests reuse the app, so the guard memoizes the user across calls (unlike real
  HTTP) — session-expiry and deactivation tests need `refreshApplication()` to simulate a new request
  lifecycle. Proven by handler-level probes (storage expired correctly; app layer masked it). Documented
  in-test. Inactive-user matrix cases for T01.5 now covered here.

### P1-T02.7 E-P0-01 checkpoint — PARTIAL EVIDENCE (exception stays open)

- Zero-state DB run (scratch `newscms_e2e_test`, dropped afterwards): `migrate:fresh --seed` clean →
  `app:create-admin` succeeds → real-HTTP login POST 302 to `/admin/dashboard` → dashboard 200 with
  content. Strongest evidence short of a clean clone.
- Honest limits: same working tree (fresh files untestable without a remote); RefreshDatabase wipes make
  an in-suite e2e impossible by design (a committed e2e test would destroy its own fixture — attempted and
  correctly refused by the test guard). E-P0-01 stays DEFERRED per the approved exception.

### P1-T02.4 Invitations + first-admin bootstrap — COMPLETE

- Files: migration `create_invitations_table`, `Invitation` model (`isUsable`), `InvitationMail` (queued)
  + text view, `StoreInvitationController` (users.manage gate, resend-by-replace, signed 48h URL),
  `AcceptInvitationController` (atomic single-use claim, role assignment, auto-login), accept Blade view,
  routes (admin store + public signed accept), `tests/Feature/Auth/InvitationTest.php` (7 tests).
- Added: `app:create-admin {email} [--role] [--password]` (PRD §21.1 bootstrap; interactive secret
  prompt validated min-10; auto-seeds roles via additive-safe RoleSeeder; user+role in transaction).
- Findings fixed at cause: queued (not sent) mail assertion; `actingAs` persistence (guest assertion first).
- Deactivation-denial rides P1-T02.6; temp-password creation path deferred (PRD allows invite *or* temp
  password; invite flow + bootstrap command fully cover P1 user creation).
- Verification: 7/7 invitation + 2/2 bootstrap tests pass; Pint + PHPStan clean.

### P1-T02.3 Password rules + login throttling — COMPLETE

- Files: `AppServiceProvider` password default → min 10 all envs (resolves P0-C7 in favor of PRD §15.5),
  `tests/Feature/Auth/PasswordSecurityTest.php` (rule boundary 9/10, throttle 429 on 6th, per-email isolation,
  identical unknown/wrong messages via `auth.failed`).
- Note: limiter itself already complied (verified in audit); tests added, no config change needed.
- Verification: 4/4 pass (15 assertions); Pint + PHPStan clean.

### P1-T02.1 Fortify baseline audit + decisions — COMPLETE

- Inventory: Fortify routes at root; web guard; email username; features were registration + resetPasswords;
  `verified` middleware on dashboard/settings with no verification feature.
- Changes (Q-P1-02: disabled public `registration()`; Q-P1-03: dropped `verified` middleware):
  `config/fortify.php` features, `FortifyServiceProvider::registerView` removed, dead register Blade removed
  (`CreateNewUser` action kept for T02.4 reuse evaluation), routes updated.
- Tests: starter-kit RegistrationTest self-skips (feature-gated, kept); new `RegistrationDisabledTest`
  locks the disabled state (route missing + POST 404 + no user created).
- Verification: focused 12 tests (10 pass, 2 self-skip, 1 pre-existing risky); Pint + PHPStan clean.
- Login limiter already 5/min per lowercased-username|IP with username=email — complies with PRD §15.5;
  T02.3 adds boundary tests, no config change needed.
