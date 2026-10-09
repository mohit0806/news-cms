# NewsCMS — Master Playbook

**Status:** Authoritative v1.0 (created 2026-10-09).
**Purpose:** The operational roadmap for implementing the approved NewsCMS requirements. Phases, dependencies, executable tasks, verification, gates, and progress tracking.
**Rule:** If something is not in the PRD, it is not in scope. If this playbook conflicts with the PRD, TRD, or ARCHITECTURE, record the conflict and fix the source document or an ADR — do not silently choose.

## 1. Source documents (do not duplicate them)

| Document | Path | Owns |
|---|---|---|
| PRD (product behaviour, scope, acceptance) | `docs/PRD.md` (Draft v1.4) | What to build |
| TRD (stack, constraints, spikes, testing) | `docs/TRD.md` (Draft v1.4) | How to build |
| Architecture (structure, ownership, boundaries) | `docs/ARCHITECTURE.md` (Draft v1.0) | Where code lives and who owns what |
| Agent instructions | `AGENTS.md` | How agents must work |
| ADRs | `docs/adr/` (does not exist yet — create on first material decision) | Material cross-document decisions |

Behaviour = PRD. Implementation constraints = TRD. Structure = ARCHITECTURE. This playbook = order, tasks, gates, evidence.

## 2. Non-negotiable boundaries

- One standalone Laravel modular-monolith per publication. No Core Composer package, no fleet/provisioning platform, no multi-tenancy in v1 (PRD D1–D2, §1.4; ARCH §1).
- Laravel-native first: routing, middleware, validation, auth, policies, queues, scheduler, cache, migrations, Artisan (TRD §2–§5; AGENTS.md §8).
- No custom kernel, DI container, event/command bus, engine registry, package-per-capability, runtime module discovery, or generator framework unless an approved requirement proves it necessary (AGENTS.md §8.1; ARCH §1.1; TRD §16.5, §17).
- Spatie Media Library is the media attachment source of truth; no duplicate media FK columns without a documented product relationship (PRD §3.1.3).
- Public pages never set cookies (PRD D12). Themes present prepared data only (ARCH §7).

## 3. Repository baseline (verified 2026-10-09)

- Laravel Livewire starter kit + Fortify 1.41 + Livewire 4.4.7 + Flux 2.20.1 + Tailwind; PHP 8.4.25 CLI; `composer.json` requires `php ^8.3`, `laravel/framework ^13.17`. No NewsCMS domain code yet (`app/` = starter-kit actions/models/providers; `database/migrations/` = users/cache/jobs only; `routes/web.php` = welcome + dashboard).
- No Master Playbook existed; no `docs/adr/`; no database/API spec beyond PRD §20 / TRD sections. This file (`docs/MASTER_PLAYBOOK.md`) is the canonical playbook — do not create `*_FINAL`, numbered, or competing variants.
- Required project skills `newscms-architecture`, `phase-execution`, `testing-and-audit`, `dependency-review` exist as **0-byte files** under `.agents/skills/` — i.e. missing. Usable skills: `laravel-best-practices`, `livewire-development`, `testing-best-practices`, `fluxui-development`, `fortify-development`, `tailwindcss-development`. Gap recorded in §9.
- No `.ai/rules/` directory — per AGENTS.md §2.1, continue with `AGENTS.md` + this playbook.

## 4. Phase index

Phases follow PRD §23 (product-first, architecture-light). Estimates omitted until P0 establishes the baseline.

| Phase | Title | Depends on | Exit gate |
|---|---|---|---|
| P0 | Baseline and decisions | — | §5.0 |
| P1 | Foundation and admin shell | P0 | §6.0 |
| P2 | Editorial core | P0, P1 | §7.0 |
| P3 | Public site and themes | P0, P2 (URLs/search feed P2-T07) | §8.0 |
| P4 | Newsroom features and growth | P2, P3 | §9.0 |
| P5 | Operations and pilot | P0–P4 | §10.0 |

Task IDs are stable (`P<phase>-T<nn>`). Status values: Planned · In progress · Implemented · Verified · Accepted · Blocked · Deferred · Rejected/out-of-scope. Verified requires the evidence in the task; Accepted requires the phase gate.

## 5. P0 — Baseline and decisions

**Objective:** Reproducible setup, pinned stack, CI, architecture alignment, and closed source-of-truth decisions. No domain features.
**Covers:** PRD §5, §23-P0, §24; TRD §2, §3, §23–§24, §26 (S3); ARCH §3, §6.1, §15.
**Entry:** Starter-kit repo as found in §3.

| ID | Task | Requirements | Acceptance |
|---|---|---|---|
| P0-T01 | Pin and verify toolchain (PHP, Laravel 13, Livewire 4, Flux 2, Tailwind, MySQL 8, Redis/Valkey, Imagick, intl) | PRD §5.1; TRD §2 | `composer show --direct`, `php -v`, `npm ls` recorded; incompatible tier flagged before P1 |
| P0-T02 | CI + quality gates (Pint, Larastan ≥ L6, Pest on MySQL+Redis services, arch-test skeleton, `composer audit`/`npm audit` on high) | TRD §23; PRD §24.1, §24.3 | `composer test` green on a clean checkout; arch tests run in the ordinary suite (no per-capability Testbench harness) |
| P0-T03 | Architecture alignment (one model namespace, route-file convention, `Domain/` grouping use, `themes/`+`modules/` locations; remove TRD/ARCH tree duplication into ARCH) | ARCH §3, §15; TRD §3 | ARCH updated; no `src/` root, no package-per-capability |
| P0-T04 | Source-of-truth ADRs: media, permissions, audit, settings (see §9 D-CONF-1…4) | ARCH §6.1; PRD §3.1.3, §20; TRD §2 | ADR or PRD/TRD edit merged **before** P1-T01/P1-T03/P1-T04 and all P2 migrations |
| P0-T05 | Confirm Q1 (TipTap), Q2 (launch languages/RTL), Q3 (Contributor scope) owners + deadlines | PRD §22 | Written confirmation or provisional default marked ASSUMPTION; blocks P2-T04, P3-T08, P1-T01 respectively |
| P0-T06 | Spike S3: Livewire routes under `/{ADMIN_PATH}` with scoped session cookie; fallback documented | TRD §5.2, §26-S3 | Prototype verdict recorded; cookie strategy frozen before P1-T02 |
| P0-T07 | Fixture baseline: `CorpusSeeder` skeleton + Devanagari/Latin slug corpus for S1/S2 | TRD §23.3, §26-S1/S2 | Seeder runs; corpora available to P2-T06/P2-T07 |

**Testing:** CI pipeline per TRD §23.1 (minus nightly). **Security/ops:** secret handling, `.env` mode, audit-tool presence.
**Exit gate P0:** fresh clone reaches working admin login per documented setup; `composer test` passes; P0-T04 decisions merged; Q1–Q3 owners set; no custom framework added.
**Risks:** version drift (Flux/Livewire/Laravel tiers); undecided source-of-truth blocking migrations — do not start P1-T01/P2 migrations until P0-T04 closes.

## 6. P1 — Foundation and admin shell

**Objective:** Secure admin with roles, settings, audit, concurrency primitives, component foundation, locale/time plumbing, notifications.
**Covers:** PRD §4, §6.1.4 (primitives), §15 (exc. homepage blocks admin in P2), §16; TRD §6.4–§6.5, §7, §11 (part), A1/A2/A5.
**Entry:** P0 gate passed.

| ID | Task | Requirements | Acceptance |
|---|---|---|---|
| P1-T01 | Permission catalog + Gates + seeded roles + generated matrix test (allow/deny per role per route/action) | PRD §4; TRD §7 | Every admin route/Livewire action guarded; matrix test green |
| P1-T02 | Auth hardening: Fortify Livewire views, invite (48 h), 10-char passwords, 5/min throttle, TOTP + `FORCE_ADMIN_2FA`, `ADMIN_PATH`, idle timeout, scoped session (P0-T06 verdict) | PRD §15.5; TRD §7 | Login/invite/2FA/throttle tests; staff marker cookie only a counting hint |
| P1-T03 | Settings store + grouped admin UI exposing the PRD §15.6 minimum controls (incl. `TAG_INDEX_MIN_ARTICLES`, featured/alt requirements, pagination, retention, SEO, OG, robots, breaking hours, analytics/trending, locale, cache/purge) | PRD §15.6, §20 | One canonical store (P0-T04); typed validation; cache invalidation tested |
| P1-T04 | Audit log + admin viewer (filters, CSV, immutable, retention job) | PRD §16 | All §16 events recorded with masked secrets; retention prune tested |
| P1-T05 | Revision + `row_version` primitives (autosave/manual kinds, conflict exception path) | PRD §6.1.4; TRD §6.4, A1 | Concurrent-save test blocks with "Changed by {user} at {time}" |
| P1-T06 | Admin component foundation (index/form/media-picker/slug/badge/dialog/lang-tabs/datetime/toast/code-editor; loading/empty/error/denied states) | PRD §15.3 | One CRUD built on the patterns; no generic admin framework, no Filament |
| P1-T07 | Locale/timezone plumbing (UTC storage, `SITE_TIMEZONE` display, `__()` coverage, admin locale separate) | PRD §8.1; TRD §11, A2 | Time-travel scheduling test; no hardcoded user strings |
| P1-T08 | Notifications (database bell + optional email, editorial events) | PRD §15.4 | Events in §15.4 notify; email off by default |

**Exit gate P1:** fresh install reaches secure admin; permission matrix green; settings/audit/revision primitives tested; component patterns reused (not duplicated).
**Risks:** permission-model decision (P0-T04) leaking into P2; scope creep into a custom admin framework.

## 7. P2 — Editorial core

**Objective:** End-to-end draft → review → schedule → publish with taxonomy, authors, media, blocks, slugs, search spike, menus/pages/homepage admin.
**Covers:** PRD §6, §7, §8.1/§8.3–§8.4, §14 (spike+driver), §15.2 (widgets data); TRD §6, §8, §10, §11, §15, §26 (S1/S2/S4/S5/S9).
**Entry:** P1 gate; P0-T04/T05 closed for the touched areas.

| ID | Task | Requirements | Acceptance |
|---|---|---|---|
| P2-T01 | Taxonomy: terms + per-language translations, 3-level categories, tag merge + redirect, archives, `noindex` under threshold, bulk move | PRD §6.2–§6.3, §8.3 | Unique `(language, slug)`; delete-blocked-with-articles; merge re-points + redirect |
| P2-T02 | Authors (separate from users, photo via Media Library, `/author/{slug}`, deactivate-not-delete) | PRD §6.4 | Author with articles cannot be deleted |
| P2-T03 | Articles core: fields/validation per §6.1.2, status machine + scheduler, preview (signed 24 h, `noindex`, uncached/uncounted), manual related, SEO fields, reading time, trash 30 d | PRD §6.1.1–§6.1.5, §6.1.7 | Reporter→review→schedule→publish at minute ±1 min; category/slug change keeps old URL via 301; no silent overwrite |
| P2-T04 | Editor blocks + `BlockSchema` + `BodyRenderer` (validate→render→persist html/text/reading-time/media-ids) + JS/PHP parity test + `articles:rerender`/`articles:migrate-bodies` | PRD §6.1.6, D14/Q1; TRD §8, S5 | Unknown blocks rejected; paste fixture spike S5 recorded; raw-HTML allowlisted + permission-gated |
| P2-T05 | Media library + queued pipeline (validate→dedupe→store→conversions→serve via `MediaUrl`/`x-picture`), folders/search/usage-meter, picker, orphan report command (report only) | PRD §7; TRD §10, S4/S9 | 12 MB JPEG returns < 3 s + background conversions; re-upload dedupes; SVG/PHP-as-JPG rejected |
| P2-T06 | Slugger (ICU transliteration, 80-char word-boundary, `-2` collisions, `UNICODE_SLUGS` option) + spike S2 on 200 Hindi headlines | PRD §8.3; TRD §11, S2 | Latin + Devanagari corpus tests green |
| P2-T07 | Search spike S1 + `ArticleSearch` interface + MySQL driver + `search:reindex` path (+Scout/Meilisearch only if S1 justifies) | PRD §14; TRD §15, A9, S1 | ≥90% top-10 labelled-corpus verdict recorded; rate limits + `noindex` |
| P2-T08 | Pages + menus + homepage-blocks admin (manual-picks-win, unpublished skipped, purge on change) | PRD §6.5–§6.7 | Reserved-slug enforcement; hidden-on-delete menu items flagged |
| P2-T09 | Editorial acceptance sweep (Pest + ≥1 Playwright happy path: create→submit→approve→scheduled-visible) | PRD §6.1.7, §24 | All §6.1.7 bullets demonstrated on MySQL/Redis |

**Exit gate P2:** P2-T09 green; media/search/slug spikes closed; `row_version` conflict + preview + schedule behaviours tested.
**Risks:** Q1/Q7 (editor choice, video-upload need); AVIF support (S9); ngram vs Meilisearch (S1) changing ops cost.

## 8. P3 — Public site and themes

**Objective:** Fast, cacheable public site on the Blade theme system with URLs, SEO/feeds, redirects, search UI, and language presentation.
**Covers:** PRD §8, §13, §14 (UI), §17, §18; TRD §5, §11 (part), §12, §18, §26-S6.
**Entry:** P2 gate (articles/taxonomy/search-driver available).

| ID | Task | Requirements | Acceptance |
|---|---|---|---|
| P3-T01 | Route groups (`cms.public`/`cms.machine`/`cms.admin`) + `PathResolver` + `cms_url()` + reserved slugs + no-trailing-slash + 410/404 rules | PRD §8.2; TRD §5 | Resolver-order test; id-lookup + 301-on-mismatch; redirect-before-resolution |
| P3-T02 | Theme system (`themes/{name}`, `theme.json` validation, resolution active→default→fallback, `theme_asset()`, `theme:check`) | PRD §17; TRD §18 | Missing view falls back, never blanks; invalid config errors safely |
| P3-T03 | Public templates + stable components (`x-picture`, `x-ad-slot`, `x-breaking-ticker`, `x-trending`, `x-related`, `x-pagination`, `x-lang-switcher`, `x-share`, `x-embed`) + `@cmsHead/Body` hooks | PRD §17 | All required templates render; themes issue no queries, enforce no permissions |
| P3-T04 | SEO + sitemaps + feeds + `robots/ads.txt` serving (canonical chain, OG `og` crop, JSON-LD, 48 h news window) | PRD §13.1–§13.2 | Unpublished/`noindex` excluded; purge-on-publish tested |
| P3-T05 | Redirects + 404 log (exact/wildcard, loop rejection, CSV import/export, 90 d prune, one-click from 404) | PRD §13.3 | Loop + validation-report tests |
| P3-T06 | Full-page cache (headers per TRD §12.2, CDN bypass rules, fragment cache, `CdnPurger` null+cloudflare with coalesced queue, `PageCache` optional) + spike S6 | PRD §18.2; TRD §12, S6 | Publish-event purge map test; public responses set no cookies (Arch test); TTLs guarantee eventual consistency |
| P3-T07 | Public search UI (`/search`, relevance→date, 2–100 chars, 30/min/IP, `noindex`) | PRD §14 | Language-scoped paginated results |
| P3-T08 | Language presentation (prefix rules, `hreflang`, switcher, RTL logical properties, subsetted Noto, legacy-font warning) + Q2 verdict | PRD §8.1/§8.4; TRD §11 | Multi-language smoke with `hi,en` when enabled |

**Exit gate P3:** templates + SEO/feed/redirect + purge tests green; `theme:check` on shipped themes; Lighthouse structure ready for P5 budgets.
**Risks:** CDN purge limits (S6) forcing shorter `s-maxage`; theme querying/permission leakage (arch test).

## 9. P4 — Newsroom features and growth

**Objective:** Breaking, discovery, embeds, scripts/ads, analytics, notifications depth, and the confirmed optional modules — without breaking cache/security.
**Covers:** PRD §9, §10, §11, §12, §15.4 (depth); TRD §9, §13, §14, §17, §26-S7/S8.
**Entry:** P3 gate; Q8 (Polls) closed; S7 pre-check done in P2.

| ID | Task | Requirements | Acceptance |
|---|---|---|---|
| P4-T01 | Breaking ticker (≤5, `breaking_until` default +6 h, read-time + scheduler expiry, 30 s cache + immediate purge) | PRD §9.1 | Expired items vanish with no editor action |
| P4-T02 | Trending/most-read + related scoring contract (tags +3, category +2, age decay, same-language, top-4 default, manual override) | PRD §9.2–§9.3 | Decay + exclusion + count tests; unpublished auto-excluded |
| P4-T03 | Embed adapters (X/IG/Threads/FB/TikTok/YT) + click-to-load loader (≤3 KB gzip) + allowlist + S7 smoke tests | PRD §9.4 D10; TRD §9, S7 | 3-embed page makes no provider requests pre-click; blocked/deleted falls back to link |
| P4-T04 | LiveBlog module (entries, composer, polling 20 s cached 10 s, `LiveBlogPosting`, end→static) | PRD §9.5, §12 | Disabled module hides routes/UI, preserves data; polling stops on `ended` |
| P4-T05 | Galleries module (CRUD, `/gallery/{slug}`, block embed, accessible lightbox) | PRD §9.6, §12 | Same enable/disable contract as P4-T04 |
| P4-T06 | Polls module — **provisional, cut candidate** (cookie-less hash + localStorage hint, rate-limited origin-checked vote, results/CSV/close) | PRD §9.7, §22-Q8 | Only builds if Q8 confirms; same enable/disable contract |
| P4-T07 | Scripts/ads manager (snippets, dual-variant ad slots with single-variant injection + script re-creation, `AdInserter`, text files, revisions/rollback, safe mode, production-only) | PRD §10; TRD §13 | GA4 scope test; mobile/desktop exclusivity; rollback restores exact code |
| P4-T08 | First-party analytics (≤1.5 KB beacon, `/t/v` Redis-only 204, dedupe 30 min, bot filter, per-minute flush additive, 5-min trending, scoped admin reads, CSV) | PRD §11; TRD §14 | Cached-article counts once per 30 min; bots uncounted; double-flush same totals |
| P4-T09 | Module isolation acceptance (≥1 optional module: absent/inactive when disabled, data preserved, core workflows pass; no generator required) | PRD §12.6; TRD §17.4 | Arch tests on dependency boundaries |
| P4-T10 | CWV impact note S8 (GA4 + ad-like loader deltas, warning thresholds) | TRD §26-S8 | Measured deltas documented; lazy defaults recommended |

**Exit gate P4:** all included §9–§11 acceptance bullets demonstrated; public-cache + no-cookie guarantees intact; embed/analytics/loader budgets met.
**Risks:** provider markup drift (adapter isolation + link fallback); tracker scripts wrecking CWV; Polls scope (cut if Q8 negative).

## 10. P5 — Operations and pilot

**Objective:** Repeatable releases, backups, observability, hardened security, accessibility, measured performance, and a four-week pilot.
**Covers:** PRD §1.5, §5.2–§5.3, §18.1, §19, §21, §24–§25; TRD §16, §19–§23, §26-S10.
**Entry:** P0–P4 gates; Q4–Q6 (hosting, server, design target) closed.

| ID | Task | Requirements | Acceptance |
|---|---|---|---|
| P5-T01 | Release process (CI→tag→migrate→caches→workers→health; backup-before-schema; rollback/forward-fix note; permission sync without overwriting admin roles) | PRD §21.1/§21.4; TRD §21.4, §16.5 | Documented repeatable process; `/up` + status checks in deploy |
| P5-T02 | Backups (nightly DB + media versioning/sync, 14+8 retention, monthly restore drill, system-page timestamp) | PRD §21.4 | Restore drill passes; media backup/restore documented |
| P5-T03 | Observability (JSON logs + request-id, Sentry scrubbed, uptime on `/up` + article URL, queue/scheduler/disk/backup/purge/cert alerts, system page) | PRD §21.3–§21.4; TRD §22 | Stale (>3 min) + failure alerts fire in staging |
| P5-T04 | Security verification (headers, upload attacks, SSRF absence, rate limits, XSS corpus, `{!! !!}` grep gate, `composer/npm audit`) | PRD §19; TRD §19, §23 | High-severity audit failure blocks merge |
| P5-T05 | Accessibility + compatibility (WCAG 2.1 AA contrast/focus/keyboard/labels/alt; last-2 browsers, iOS 15+, Android Chrome) | PRD §24.2 | Default theme + admin pass targeted checks |
| P5-T06 | Performance/load validation (TTFB/CWV/admin-p95 budgets, query-count assertions, k6 incl. S10 beacon 100 rps, Lighthouse CI) | PRD §1.5, §18.1; TRD §20, §23 | Budgets in §18.1/TRD §20.1 met on the reference server or deltas documented |
| P5-T07 | Q4–Q6 confirmations (hosting target, min server, 100k/day + 10x design target) | PRD §22 | Signed off before pilot |
| P5-T08 | Pilot + completion audit (4 weeks real articles, setup-from-docs, backup-restore, release drill; phase-gate audit per §11) | PRD §1.5 | All four §1.5 bullets evidenced or explicitly accepted as findings |

**Exit gate P5 (launch):** P5-T02/T06/T08 evidenced; no unresolved Critical/High findings (or written acceptance); pilot criteria met.

## 11. Gates and audit procedure

Each phase gate verifies: task acceptance met; focused + relevant broader tests run; traceability intact; no hidden Critical/High issues; architecture/data-ownership compliant; docs consistent; deferrals recorded; report honest. Severity: Critical (blocks launch/progress) · High (blocks phase unless accepted) · Medium/Low (deferrable with entry). A phase is complete only on exit criteria or written acceptance of findings. Human review at phase gates and for material scope/security/data/architecture/package/irreversible-migration decisions — not per task.

## 12. Scope classification (proposed where PRD is silent)

- **Launch:** §4–§8, §10–§11, §13–§19 admin/seo/search/theme/cache/security/ops baselines, LiveBlog + Galleries modules (pending Q8 for Polls only).
- **After launch:** Meilisearch driver (if S1 defers), AVIF (if S9 defers), `newscms:restore-test` automation, `newscms:status --json` richness.
- **Optional/conditional:** Polls (Q8), `UNICODE_SLUGS=true` installs (S2 fallback), `EMBED_LOAD_MODE=lazy`, Cloudflare driver (else `null` + origin cache).
- **Out of scope (v1):** PRD §1.3 list (paywall, comments, e-paper, push, social auto-post, multi-tenancy, marketplace, contact builder, newsletter, edit-locking, consent banner, header-bidding, ad reporting, AMP, mobile apps, public API) + child themes/marketplace/builder/executable extensions (PRD §17) + Octane (TRD §2).
- **Awaiting decision:** Q1–Q8 (PRD §22), S1–S10 (TRD §26), D-CONF-1…6 below.

## 13. Traceability (requirement → tasks)

| PRD | TRD | Tasks |
|---|---|---|
| §4 roles/permissions | §7 | P1-T01, P1-T02 |
| §5 stack/deploy | §2, §16, §21 | P0-T01, P5-T01 |
| §6.1 articles | §4, §6.4, §8 | P1-T05, P2-T03, P2-T04 |
| §6.2–§6.7 taxonomy/pages/menus/home | §5.3, §6.3, §18 | P2-T01, P2-T02, P2-T08 |
| §7 media | §10 | P2-T05 |
| §8 lang/URLs/slugs | §5, §11 | P1-T07, P2-T06, P3-T01, P3-T08 |
| §9 breaking/trending/related/embeds/live/gallery/polls | §9, §12.2, §17 | P4-T01…T06 |
| §10 scripts/ads | §13 | P4-T07 |
| §11 analytics | §14 | P4-T08 |
| §12 modules | §17 | P4-T04…T06, P4-T09 |
| §13 SEO/feeds/redirects | §5.3, §12 | P3-T04, P3-T05 |
| §14 search | §15 | P2-T07, P3-T07 |
| §15 admin | §7, §8.4, §24 | P1-T02…T08 |
| §16 audit | §4.3, §6.8 | P1-T04 |
| §17 themes | §18 | P3-T02, P3-T03 |
| §18 perf/cache | §12, §20 | P3-T06, P5-T06 |
| §19 security | §19 | P5-T04 |
| §20 data model | §6 | P0-T04, P1-T01/T03/T04, P2-T01…T03/T05 |
| §21 ops/install | §16, §21, §22 | P5-T01…T03 |
| §23 delivery plan | §26 spikes | P0-T06/T07, phase entries |
| §24 quality | §23, §24 | P0-T02, all gates |

Unmapped: none material; Polls maps only if Q8 confirms (else Deferred).

## 14. Verification commands (repo-configured — do not invent others)

- `composer test` (= `config:clear` + `pint --test` + `phpstan analyse` + `php artisan test`); narrow: `php artisan test --filter=<name>` or file path; style: `vendor/bin/pint --dirty --format agent` when PHP changed (AGENTS.md §10).
- Frontend: `npm run build` / `npm run dev` for manifest issues; Vitest/Playwright/k6 per TRD §23 when configured.
- DB: prefer factories/tests over Tinker models (AGENTS.md §8). Read-only inspection via schema/test tooling.

## 15. Agent workflow (per AGENTS.md + available skills)

1. Read `AGENTS.md` §2–§4, this playbook's current phase/tasks, and the cited PRD/TRD/ARCH sections. 2. Activate the minimum skills for the task type (AGENTS.md §4.2); with the §3 gap, substitute: architecture → `laravel-best-practices` (rules/architecture) + ARCH doc; phase discipline → this playbook's task contract; testing → `testing-best-practices`; dependencies → `dependency-review` file is empty so follow AGENTS.md §6.4 + TRD §2 policy and request approval. 3. Inspect sibling code/routes/config/migrations/schema and installed versions before changing code. 4. Make the smallest compliant change; no unrelated cleanup. 5. Verify per §14 + task checks; run Pint when PHP changed. 6. Record status/evidence honestly; report conflicts via AGENTS.md §3 (exact statements, consequences, ADR check, minimal fix, stop on material irreversibles).

## 16. Risks, decisions, deferred work

- R1 Phase-0 decisions slipping into migrations (media/permissions/audit/settings) → gate P0-T04.
- R2 Devanagari search/slug quality (S1/S2) → P2-T06/T07 decide; fallback Meilisearch / `UNICODE_SLUGS=true`.
- R3 Livewire route relocation (S3) → P0-T06 fallback (cookie `/` + CDN bypass).
- R4 Provider embed drift → adapter isolation + smoke tests + link fallback.
- R5 Purge storms/limits → coalescing + TTL fallback.
- R6 Tracker/CWV damage → S8 thresholds + lazy slots.
- R7 Optimistic estimates → re-estimate after P0 (PRD §25).

---

*End of playbook. Change product behaviour only in the PRD, constraints only in the TRD, structure only in ARCHITECTURE, execution only here — same PR for linked edits, ADR in `docs/adr/` for material choices.*
