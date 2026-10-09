# TRD — NewsCMS (working name)

**Companion to:** `PRD.md` (the PRD says *what*; this document says *how*).
**Status:** Draft v1.4 — Laravel-native implementation, package inventory, and architecture responsibilities clarified.
**Updated:** 2026-10-09.
**Rule:** PRD defines product behaviour; this TRD defines implementation constraints. If they conflict, do not silently choose: record the conflict and update both documents or create an ADR. Open technical questions belong in §26 with an owner and decision phase.

Items marked **[ASSUMPTION]** inherit an unconfirmed PRD assumption (PRD §22). Do not turn an assumption into a permanent dependency without resolving it in the phase that needs it.

---

## 1. Technical decisions and clarifications

The following decisions clarify implementation details. They do not override product behaviour in the PRD. When a technical clarification changes user-visible behaviour or schema requirements, update the PRD in the same change.

| # | Area | Technical decision | Reason/status |
|---|---|---|---|
| A1 | Optimistic concurrency | Use integer `row_version` on editable publishable records (at minimum articles and pages); the form submits the version it loaded. Increment on each successful save. | Avoids same-second timestamp collisions and silent overwrites. |
| A2 | Time | Store timestamps in UTC (`APP_TIMEZONE=UTC`). Use `SITE_TIMEZONE` (default `Asia/Kolkata`) for display and site-local calendar boundaries. | Portable data and consistent scheduling. |
| A3 | Safe mode | A `?no-scripts=1` safe-mode request removes managed scripts/ads and is `no-store`. It is a mitigation for broken managed scripts, not an authentication mechanism. | Public pages remain stateless. |
| A4 | Content rendering | Structured editor JSON is schema-validated and rendered by controlled server-side code. Apply HTML sanitization only to explicitly supported raw-HTML blocks, custom HTML blocks, and imported HTML. | Prefer a constrained content model over storing arbitrary editor-generated HTML. |
| A5 | Analytics exclusion | Set a staff marker cookie after admin login; the analytics beacon skips counting when it is present. CDN rules bypass cache for staff/session cookies. The marker is a counting hint, not an authorization control. | Cached public pages cannot reliably infer a logged-in user. |
| A6 | Media lifecycle | Media records expose processing status and processing timestamp (or equivalent state in the selected Media Library integration). | Admin needs accurate processing/failure state. Do not duplicate package-owned records without a reason. |
| A7 | Deployments | Additive releases do not enable maintenance mode by default. Downtime is permitted only when a release explicitly requires it and the rollout plan says so. | Safer application deployments. |
| A8 | Redis | Prefer separate cache and durable Redis/Valkey instances. If hosting limits make this impossible, document capacity/eviction trade-offs and do not use an eviction policy that can silently drop durable queues. | Prevents cache eviction from losing jobs. |
| A9 | Search | Validate MySQL full-text behaviour for Hindi/Devanagari before launch. Keep the search interface small enough to switch to Meilisearch if tests show unacceptable quality. | Evidence-based engine choice. |

---

## 2. Technology stack

| Layer | Choice | Notes |
|---|---|---|
| Language | PHP 8.3+; use a currently supported PHP release compatible with the selected Laravel major | Require `ext-intl`, `ext-imagick` (or a verified alternative), `ext-redis` when using Redis, `ext-pdo_mysql`, `ext-mbstring`, and `ext-fileinfo`. Pin and test exact versions at kickoff. |
| Framework | Laravel, current stable major compatible with the deployment baseline | Laravel is the application framework. Use its service container, service providers, routing, events, queues, scheduler, cache, policies, migrations, and Artisan. No custom Kernel or parallel DI/event/command framework. |
| Admin UI | Livewire, Blade, Alpine.js where needed, Tailwind CSS, and Flux UI | Custom admin workflows; Flux is the component layer, not a complete admin system. Verify Laravel/Livewire/Tailwind/Flux compatibility together before pinning. Admin and public assets use Vite. No Filament dependency. |
| Editor | Tiptap-based block editor **[ASSUMPTION Q1]** | Server-side schema validation and renderer are authoritative; the browser editor is not trusted to enforce content safety. Confirm editor choice before implementation. |
| Database | MySQL 8.0+ with `utf8mb4` | MariaDB 10.6+ is a best-effort target unless separately certified. SQLite is not the authoritative integration-test database. |
| Cache / queue / stats | Redis 6.2+ or compatible Valkey; separate cache and durable instances where the deployment permits (A8) | Cache instance may use eviction; durable instance must not evict queue/stat data and should use AOF `everysec`. Document a fallback for small hosts that cannot run two instances. |
| Search | MySQL FULLTEXT initially, with Laravel Scout + Meilisearch behind a small search interface | Search tokenizer/language behaviour is a spike, especially for Hindi/Devanagari. Do not build a generic search framework. |
| Images | Intervention Image v3 with Imagick driver; optional libvips only after verification | WebP required; AVIF only when supported by the deployed image stack. |
| HTML sanitization | `symfony/html-sanitizer` for approved raw-HTML/import surfaces | Structured editor content is validated against a schema and rendered by controlled code. |
| Authentication | Laravel authentication/Fortify where needed for password reset and TOTP 2FA | Custom Livewire views; no public reader accounts in v1. |
| Planned application packages | `spatie/laravel-medialibrary`, `spatie/laravel-permission`, `spatie/laravel-settings`, `spatie/laravel-activitylog` | Selected by ADR-0001…0004 (P0-T04). Install at the phase that needs each package after dependency-review approval; re-confirm compatibility, license, and maintenance at install time. Do not duplicate Media Library's attachment records in custom tables. |
| Planned search integration | `laravel/scout` with a Meilisearch driver if the search spike justifies it | Use only behind a small application search interface. Keep MySQL FULLTEXT as the initial option until Hindi/Devanagari tests justify an external search service. |
| Phase-gated packages to evaluate | `spatie/laravel-sitemap`, `spatie/laravel-sluggable`, `spatie/laravel-backup`, `spatie/laravel-health`, `spatie/laravel-responsecache`, `sentry/sentry-laravel`, Flysystem S3 adapter, and an SEO metadata package if useful | Not automatically required. Evaluate each against a specific requirement when that phase begins; prefer Laravel-native or existing application functionality when it is adequate. |
| UI package | Livewire Flux UI (`livewire/flux`; exact tier/license to be confirmed) | Required UI direction selected by the project. Confirm licensing and compatibility with the chosen Livewire, Tailwind, and Laravel versions before implementation. |
| Tests and quality | Pest, Larastan (level 6 target), Pint, Vitest, Playwright, k6 | Keep architecture tests in the ordinary Laravel test suite. Do not require Orchestra Testbench for each capability unless an independently reusable package later justifies it. |
| Web server | Nginx + PHP-FPM + OPcache; Supervisor/systemd for long-running workers | Octane is not required for v1. Re-evaluate only after measured bottlenecks. |
| CDN | Cloudflare driver or null driver | CDN is optional and must not be required for local development or correctness. |

**Dependency policy:** this is a planned inventory, not a promise that every listed package must be installed and not a permanent closed allowlist. Before adding a dependency, record the concrete requirement, candidate package, alternatives, license, maintenance/security status, compatibility with the selected stack, and the phase that needs it. The developer/owner approves the addition before an agent installs it. Packages may be added in later phases when a real requirement emerges; do not install speculative dependencies early. Prefer permissive licenses where practical and review transitive obligations. CI runs Composer/npm security and license checks appropriate to the chosen tools.

---

## 3. Repository, package, and directory structure

### 3.1 Application repository and layout

Build NewsCMS as one ordinary Laravel application in one source repository. Do not create a separate Core Composer package, customer starter application, customer-specific repositories, fleet repository, or multi-install rollout system. Keep cohesive capabilities in application namespaces/directories; split code into packages only if a demonstrated technical need emerges later.

| Artifact / codebase | Purpose |
|---|---|
| `newscms` | The Laravel application repository containing the admin portal, news domain, public website, themes, tests, and deployment configuration |

### 3.2 Canonical directory layout

The canonical directory tree and ownership of folders belong in `ARCHITECTURE.md`, because they define the codebase's structure and responsibility boundaries. Keep that tree aligned with a normal Laravel application; do not introduce a parallel `src/` application root or package-per-capability layout just to express modularity.

Implementation should follow Laravel conventions by default:

- Application classes live under `app/` and use the normal `App\` namespace, organized into focused subdirectories/namespaces where useful.
- Framework configuration lives in `config/`; migrations, factories, and seeders live under `database/`.
- Routes use Laravel's normal route files and registration mechanisms. Add a dedicated route file only when it materially improves clarity, and register it through Laravel's normal routing setup.
- Blade views and Vite-managed assets live under `resources/`; public files live under `public/`.
- Tests use the ordinary Laravel test suite and standard test directories.
- Keep themes and genuinely optional first-party modules in clearly defined locations documented by `ARCHITECTURE.md`; do not build dynamic package discovery to load them.

Do not copy a directory tree into this TRD. The TRD specifies technical rules and runtime behaviour; `ARCHITECTURE.md` specifies the canonical structure. The architecture document is to be aligned to these Laravel-native conventions before implementation work relies on its tree.

### 3.3 Loading rules
- All application migrations live in `database/migrations/` and run through Laravel’s normal migration system.
- Shared CMS views may use the `newscms::` namespace if registered by the application. The active theme's `views/` path is resolved before the default theme; missing views fall back to the default theme.
- Configuration uses Laravel’s standard config files and environment variables.

---

## 4. Application architecture and conventions

### 4.1 Laravel-native layers

```text
HTTP routes / Controllers / Livewire components
        ↓
Application Actions / Use Cases
        ↓
Domain models, policies, services, and query objects
        ↓
Laravel events, listeners, queued jobs, notifications, and scheduled commands
```

Use this as a practical guide, not a mandatory class-per-operation ceremony. A simple operation may stay in a small service/controller method when it remains clear and testable. Extract an Action when it represents a meaningful use case, transaction boundary, reuse point, or test seam.

Rules:
1. Controllers and Livewire components handle request/UI orchestration, validation, authorization, and responses; complex business rules belong in application/domain code.
2. Blade views and themes present prepared data and do not issue ad-hoc database queries.
3. Use Eloquent models and migrations normally. Keep model responsibilities focused; use query objects for complex/reused public read paths.
4. Use Laravel events/listeners for side effects such as cache invalidation, search indexing, notifications, and audit records. Avoid custom event buses.
5. Keep cohesive capabilities in clear namespaces/directories (for example `Domain/Articles`, `Domain/Media`, `Domain/Taxonomy`, `Domain/Themes`) within the Laravel application. A conceptual capability boundary is not automatically a Composer package.
6. Define interfaces when there is a real alternative implementation, a stable extension boundary, or a useful test seam—not for every class.
7. Do not add a custom application Kernel, DI container, registry freeze lifecycle, dynamic dependency graph, or package-per-capability architecture.

### 4.2 Application Action convention

Where an Action is useful, give it one clear use-case responsibility, explicit inputs, authorization at a clear boundary, and a transaction around related database writes. Prefer a `handle()` method for consistency, but do not introduce wrapper classes that merely forward one call without adding meaning.

### 4.3 Events and side effects

Use named Laravel events for stable business transitions (for example article published/unpublished) when multiple independent side effects need to respond. Keep event payloads small and explicit; prefer IDs/value objects over serializing entire Eloquent models into long-lived queued messages. Listeners/jobs must be idempotent where retries are possible.

---

## 5. Routing, request lifecycle, middleware

### 5.1 Three route groups

| Group | Middleware | Notes |
|---|---|---|
| `cms.public` | `TrustProxies`, `SetLocaleFromPath`, `SecurityHeaders`, `PublicCacheHeaders`, `AssertNoCookies` (test/dev + prod safety net strips `Set-Cookie`) | **No** session, **no** cookies, **no** CSRF (D12). Built as its own group, not Laravel's `web` group |
| `cms.machine` | `TrustProxies`, `throttle:<named limiter>`, `OriginCheck` | `/t/v`, `/live/{id}/entries`, `/poll/{id}/vote`. CSRF-exempt, stateless |
| `cms.admin` | `web` group, `auth`, `EnsureUserActive`, `EnsureTwoFactor`, `SetAdminLocale`, `SecurityHeaders`, `NoStore` | Mounted under `/{ADMIN_PATH}` |

### 5.2 Cookie scoping (supports D12 and A5)
- Session cookie name `cms_session`, **path `/{ADMIN_PATH}`**, so it is never sent on public URLs.
- Livewire's update (and upload/script) endpoints must live under the admin prefix so they receive the cookie. **Spike S3** confirms Livewire allows relocating these routes. Fallback if not: session cookie path `/` plus a CDN bypass rule for any request carrying `cms_session`.
- `cms_staff=1` (non-HttpOnly, path `/`, set at admin login, cleared at logout) marks staff for beacon exclusion and cache bypass.

### 5.3 Public path resolution (`PathResolver`)
Explicit routes for fixed prefixes come first (`/search`, `/tag/*`, `/author/*`, `/gallery/*`, `/preview/*`, `/sitemap*.xml`, `/feed*`, `/robots.txt`, `/ads.txt`, `/app-ads.txt`, `/t/v`, `/live/*`, `/poll/*`). Then one fallback route `GET {path}` (`.*`) runs:

1. Strip the language prefix if the first segment is an enabled non-default language; set locale.
2. **Redirects**: exact lookup, then wildcard prefix. Apply status (301/302/410).
3. **Article**: match `^(?<cats>.+)/(?<slug>.+)-(?<id>\d+)$` → load by `id` and `language`. If found and published: compare requested category path and slug with canonical; mismatch → **301** to canonical; match → render. Found but `unpublished` → **410**; trashed or missing → continue.
4. **Category**: look up the slug path in the cached slug map (`categories:slugmap:{lang}`, Redis, rebuilt on category change).
5. **Page**: single segment, cached page-slug map.
6. 404, and upsert into `not_found_log` (queued, debounced).

Other rules: no trailing slash (301 to canonical); `HEAD` handled like `GET`; reserved slug list enforced by validation at save time (PRD §8.2) and by a unit test that checks no seeded slug collides.

### 5.4 URL generation
Single helper `cms_url('article', $article)` / `cms_url('category', …)` is the only way themes and code build public URLs. It adds the language prefix, builds `/{cat-path}/{slug}-{id}`, and is the one place to change if the URL scheme changes.

---

## 6. Data layer

### 6.1 Conventions
- Engine InnoDB, `utf8mb4`, collation `utf8mb4_unicode_ci` (or `utf8mb4_0900_ai_ci` on MySQL 8). Primary keys `bigint unsigned`.
- All datetimes stored in UTC. Eloquent casts to `CarbonImmutable`; display conversion only in presenters using `SITE_TIMEZONE`.
- Foreign keys everywhere; `ON DELETE RESTRICT` by default, `CASCADE` only on pure pivot/child rows (`article_tag`, `gallery_items`, `poll_options`, …).
- Soft deletes only where PRD says (articles, pages, media). Authors/categories are never hard-deleted when in use.
- `body_json` is `longText` with an `array` cast (portable to MariaDB). Config-like columns (`config_json`, `scope_json`, `settings.value_json`) use native `json`.
- `Model::shouldBeStrict()` outside production (lazy-loading, silent attribute discard, missing attributes all throw). `preventLazyLoading` also logs in production instead of throwing.
- Mass assignment: explicit `$fillable`; never `$guarded = []`.

### 6.2 Query scopes (the only way to filter publicly visible content)
`published()` (status = published, `published_at <= now`), `inLanguage($lang)`, `inCategory($id)`, `withListColumns()` (excludes heavy columns), `newestFirst()`. Public queries must use `published()`; an Arch test fails if public Query classes touch `Article` without it.

### 6.3 Translation tables
Trait `HasTranslations` on `Category` and `Tag`: `translation(?string $lang = null)` returns the row for the language, falling back to the default language. List queries eager-load only the needed language: `with(['translations' => fn ($q) => $q->where('language', $lang)])`.

### 6.4 Concurrency (A1)
`articles.row_version int unsigned default 1`. `SaveArticle` runs `UPDATE … WHERE id = ? AND row_version = ?` (or compares inside a locked transaction); zero rows affected → `ConflictException` → UI conflict dialog (PRD §6.1.4). Autosave never touches `articles`; it writes only an `article_revisions` row of kind `autosave`.

### 6.5 Permissions as code
A focused permission catalog defines the application’s permission keys. Seed defaults safely and never overwrite administrator-configured role permissions during routine deployment.

### 6.6 Migrations policy
- Reversible `down()` always written; **additive** within a major (new nullable/defaulted columns, new tables, new indexes). No drops/renames within a major.
- Rename or drop = two releases (add + backfill + dual-read, then remove in next major).
- Large-table index/column changes use `ALGORITHM=INPLACE, LOCK=NONE` where supported.
- CI nightly job runs migrations against a 200k-article seeded database and reports duration and lock waits.

### 6.7 Media usage index
`UpdateMediaUsage` (sync listener) walks the saved JSON for media ids and rewrites `media_usages` rows for that owner. This powers "used in N items" and delete warnings without scanning articles.

### 6.8 Retention jobs
Trash purge (30 d default), revisions pruning (keep 50 manual, 1 autosave), `not_found_log` (90 d), `audit_logs` (`AUDIT_RETENTION_DAYS`), failed jobs (14 d), orphan media report (weekly, report only).

---

## 7. Authentication and authorization

- Single `web` guard, session driver on the durable Redis instance. Users are staff only.
- **Fortify** provides TOTP 2FA, password reset and login throttling plumbing; views are our Livewire pages. `FORCE_ADMIN_2FA` enforced by `EnsureTwoFactor`.
- Password rules: min 10 chars; optional `uncompromised()` check (HIBP) controlled by config (off by default in air-gapped installs).
- Invites: signed URL, 48 h expiry, single use.
- Permission checks: every key in the permission catalog is registered as a Gate ability, resolved from a per-role permission set cached in Redis (`role:{id}:perms`, invalidated on role change).
- Ownership rules live in Policies, e.g. `ArticlePolicy::update` = `articles.edit.any` **or** (`articles.edit.own` and `created_by = user` and status ∈ {draft, in_review}).
- Deactivating a user: `EnsureUserActive` rejects their next request and their sessions are deleted.
- Livewire rules: authorize inside every public action method; `#[Locked]` on ids/ownership properties; never expose full models with hidden attributes; validate all inputs in the action, not just in the form.
- Permission matrix test: a generated test iterates the PRD §4.2 matrix and asserts allow/deny per role for every guarded route and Livewire action.

---

## 8. Editor and content rendering **[ASSUMPTION Q1]**

### 8.1 Stored format
```json
{ "schema": 1, "type": "doc", "content": [ { "type": "paragraph", "content": [ … ] }, … ] }
```
`schema` is a version integer; a migration framework (`BodyMigrator`) upgrades old content lazily on edit and in bulk via `articles:migrate-bodies`.

### 8.2 Single source of truth for blocks
- PHP `BlockSchema` defines allowed node types, their attributes (type, bounds), and allowed marks.
- JS Tiptap extensions must match. A **parity test** (Vitest exports the JS node list to JSON; Pest compares it to `BlockSchema`) fails CI on any drift.
- Max body size 2 MB (validated server-side).

### 8.3 Rendering pipeline (`BodyRenderer`)
1. Validate JSON against `BlockSchema`; reject unknown nodes/attributes/marks (never silently ignore).
2. Walk the tree with a visitor; each node type has a Blade partial `newscms::blocks.{type}` (escaped output; links get `rel="noopener noreferrer"`, external links `target` as configured).
3. Produce `RenderedBody { html, text, readingTimeMin, mediaIds[], embeds[] }`.
4. Persist `body_html`, `body_text`, `reading_time_min`; update `media_usages`.
5. Blocks use stable class names (`cms-block cms-block--image`) so themes style via CSS only.
6. `articles:rerender` (chunked, queued) re-renders all bodies after a renderer or block-template change.

Ad slots are **not** stored in `body_html`; they are inserted at render time on the JSON tree by `AdInserter` (counting top-level paragraph nodes), so changing ad rules never requires re-rendering articles (§13).

### 8.4 Editor integration (Livewire + Alpine)
- Editor container is `wire:ignore`. An Alpine component owns the Tiptap instance.
- State syncs to Livewire on blur, on a 10 s debounce, and immediately before any `save`/`submit` call (`$wire.set('body', json, false)` then call the action) — not on every keystroke.
- Autosave: Alpine timer (30 s) calls `autosave()` only when dirty.
- Toolbar, slash-menu, bubble menu are our own Alpine/Blade UI (Tiptap is headless).
- Embed, gallery, poll, image nodes use a node view that renders a placeholder card in the editor (no third-party loads in admin; §9).

### 8.5 Paste handling (pending PRD decision)
Design hook, so either outcome is cheap: clipboard HTML goes through (1) client-side sanitizer (DOMPurify) → (2) ProseMirror parse rules mapped to the schema → (3) mark filter driven by `PASTE_MODE` (`structure` default | `keep-styles`). Pasted images (blobs, `data:` URLs) are uploaded to the media library and replaced by image nodes. Fixture-driven spike **S5** (Word, Google Docs, web pages, WhatsApp-copied text) decides what the default mode preserves.

### 8.6 Sanitizer scope (A4)
`HtmlSanitizer` (Symfony) config: allowed elements/attributes list, schemes `http|https|mailto|tel`, forced `rel`, no `style` attribute except where `PASTE_MODE=keep-styles` explicitly allowlists properties (`color`, `background-color`, `text-align`, optionally `font-size`/`font-family`). Applied to: raw HTML block, custom-HTML homepage block, imported content. Never applied "instead of" schema validation.

---

## 9. Embeds (PRD §9.4, D10)

### 9.1 Adapter contract
```php
interface EmbedAdapter
{
    public function key(): string;                              // 'x', 'instagram', 'threads', …
    public function detect(string $input): ?EmbedData;          // URL or pasted embed code → provider + permalink + safe fields; null if unrecognised
    public function render(EmbedData $d): string;               // official markup regenerated from stored fields
    public function placeholder(EmbedData $d): string;          // our click-to-load card
    public function scriptHost(): array;                        // allowlisted script/iframe hosts (for CSP & sanitizer)
}
```
Adapters register through `Cms::embeds()->register()` (the application ships its supported providers; optional modules may add more).

### 9.2 Safety rules
- Pasted HTML is **parsed for the permalink only** and discarded. Nothing from the paste is stored or echoed.
- The server makes **no outbound request** to fetch or validate user-supplied URLs (no SSRF surface in v1).
- Script URLs are never read from the DOM. The page carries one server-generated `<script type="application/json" id="cms-embeds">` map {providerKey → script URL, rescan hint}; the loader only uses keys it finds there.

### 9.3 Provider reference table
*Markup, script URLs, and re-scan calls below are the **starting point**; each is re-verified against the provider's current official docs when its adapter is built (spike S7) and covered by a smoke test.*

| Provider | Accepted inputs | Stored fields | Official markup (regenerated) | Script | After insert |
|---|---|---|---|---|---|
| X | `x.com` / `twitter.com` `/{user}/status/{id}` | permalink | `blockquote.twitter-tweet` with a link to the post | `platform.twitter.com/widgets.js` | `twttr.widgets.load(el)` |
| Instagram | `/p/`, `/reel/`, `/tv/` permalinks | permalink, captioned flag | `blockquote.instagram-media` with `data-instgrm-permalink` | `www.instagram.com/embed.js` | `instgrm.Embeds.process()` |
| Threads | `threads.net`/`threads.com` `/@{user}/post/{code}` or `/t/{code}` | permalink | `blockquote.text-post-media` with `data-text-post-permalink` | `www.threads.com/embed.js` | adapter-specific (script scans on load; re-insert if already loaded) |
| Facebook | post / video / reel URLs | permalink, kind | `div.fb-post` / `div.fb-video` with `data-href` | Facebook JS SDK | `FB.XFBML.parse(el)` |
| TikTok | `/@{user}/video/{id}` | permalink, id | `blockquote.tiktok-embed` with `cite` | `www.tiktok.com/embed.js` | adapter-specific |
| YouTube | `watch?v=`, `youtu.be/`, `/shorts/`, `/live/` | video id, start time | placeholder = thumbnail + play; on click official player iframe via `youtube-nocookie.com` (autoplay) | none | none |

### 9.4 Public loader (`embed-loader.js`)
- Placeholder markup: `<div class="cms-embed" data-provider="x" data-id="…" style="min-height:…">` containing provider label, "Load post" button, and a plain link to the original.
- On click (or intersection when `EMBED_LOAD_MODE=lazy`): insert official markup → `loadScriptOnce(src)` (promise, one `<script>` per provider per page) → run the provider's after-insert call → remove placeholder.
- Failure (script error, blocked, timeout 8 s): restore placeholder with "Open on {Provider}" link.
- Reserved heights per provider (tunable) to limit CLS; YouTube uses a 16:9 aspect-ratio box.
- Budget: loader ≤ 3 KB gzip.

### 9.5 Admin
Editor node view shows the same placeholder plus a "Load preview" button that reuses the loader. Nothing third-party loads in admin unless clicked.

---

## 10. Media pipeline

### 10.1 Upload flow
1. **Validation before storing:** size cap by type; real MIME via `finfo`; extension allowlist; for images `getimagesize`/Imagick *ping* (no decode) to reject dimensions > 12 000 px per side or > 100 megapixels (decompression-bomb guard).
2. **Dedupe:** compute SHA-256 on the temp file; if a non-deleted `media` row with that hash exists, discard the upload and return the existing record (tell the user).
3. Store original at `{yyyy}/{mm}/{uuid}/original.{ext}` on `MEDIA_DISK`; insert `media` row with `status = processing`.
4. Dispatch `ProcessMedia` on queue `media`.
5. Small files (≤ 20 MB) use Livewire temporary uploads (direct-to-S3 temporary upload when `MEDIA_DISK` is S3-compatible). Large files (video/audio) need a chunked approach (spike S4: candidates Uppy + tus/XHR chunking); Nginx `client_max_body_size` and PHP `upload_max_filesize`/`post_max_size` are provisioned from the `MAX_UPLOAD_*` env values by the install command.

### 10.2 `ProcessMedia` job chain
1. Read metadata (dimensions, duration for audio/video if `ffprobe` exists, else null), apply EXIF orientation, convert to sRGB, strip metadata, downscale original if longest side > 4000 px.
2. Create conversions in a batch: WebP widths 320/640/1024/1600 first (queue `media`), then AVIF (queue `media-low`, only when the encoder is available — checked at install, spike S9), then named crops `thumb`, `card`, `hero`, `og`.
3. Focal crop: target box has the required aspect ratio, is centred on `(focal_x, focal_y)`, then clamped inside the image.
4. Write `media_conversions` rows; set `status = ready`, `processed_at`.
5. Retries 3× with backoff (30 s, 2 min, 10 min); then `status = failed` and show on the system page with retry.
6. Worker limits: memory 512 MB, timeout 300 s; Imagick resource limits set via `policy.xml` (shipped by the install command) which also disables risky coders (URL, HTTP, MVG, MSL, EPHEMERAL, TEXT, LABEL).

### 10.3 Serving
- URL generation only through `MediaUrl` (applies `MEDIA_CDN_URL`).
- Originals and conversions get `Cache-Control: public, max-age=31536000, immutable` (S3 object metadata at upload, or Nginx for local) — paths are content-unique so this is safe.
- `<x-picture>` reads `media_conversions` to build `srcset`; it falls back to the original while status ≠ ready.
- Animated GIF: kept as original with a static WebP poster; size capped by the image limit.

### 10.4 Storage accounting and cleanup
- `StorageUsage` service sums `media.size_bytes + conversions.size_bytes`, cached, updated incrementally; reconciled daily.
- Delete = soft delete; a job hard-purges files and rows after 7 days. Orphan/missing-file report weekly (report only).

---

## 11. Languages

- `SetLocaleFromPath` sets `app()->setLocale()` for content and views. Admin UI language is separate (user preference, English only in v1).
- **Slugs:** `Slugger` uses PHP `intl` `Transliterator::create('Any-Latin; Latin-ASCII')` then lowercases, hyphenates, trims to 80 chars at a word boundary. `UNICODE_SLUGS=true` skips transliteration and keeps letters/marks/digits (NFC-normalized). Spike S2 compares ICU output with `voku/portable-ascii` on 200 real Hindi headlines.
- **hreflang:** `HreflangResolver` finds siblings by `translation_group_id` (only published ones) and emits alternates; no alternates when there is no sibling.
- **Dates/numbers:** Carbon `isoFormat` with locale; Latin digits by default.
- **Fonts:** theme build step subsets the `docs/design.md` typefaces for the enabled scripts (fonttools `pyftsubset`) into WOFF2 with `unicode-range` (variable font preferred where it keeps the §20 budgets); two weights max by default; preload the primary text font.
- **RTL:** `<html dir>` from language config; Tailwind logical-property utilities only (lint rule bans `ml-*`/`mr-*`/`left-*`/`right-*` in theme and CMS views).
- **Time:** all storage UTC (A2); presenters convert to `SITE_TIMEZONE`.

---

## 12. Caching and purging

### 12.1 Layers
CDN → (optional origin response cache) → fragment/query cache (Redis cache instance) → MySQL.

### 12.2 HTTP headers (public, cacheable responses)

| Page | `Cache-Control` | Notes |
|---|---|---|
| Article | `public, max-age=60, s-maxage=600, stale-while-revalidate=60` | Browser keeps it short so purges feel instant |
| Homepage | `public, max-age=30, s-maxage=60, stale-while-revalidate=30` | |
| Category/tag/author listing | `public, max-age=30, s-maxage=120, stale-while-revalidate=60` | |
| Sitemaps/feeds | `public, s-maxage=300` | Purged on publish |
| robots/ads.txt | `public, s-maxage=3600` | Purged on save |
| Search, preview, admin, machine endpoints (except live) | `no-store` | |
| `/live/{id}/entries` | `public, s-maxage=10` | |
| Hashed static assets | `public, max-age=31536000, immutable` | |

`Vary: Accept-Encoding` only. Language is in the path, so no `Vary: Cookie`/`Accept-Language`.

### 12.3 CDN bypass rules (Cloudflare Cache Rules)
Bypass when request carries `cms_staff` or `cms_session`, when path starts with `/{ADMIN_PATH}`, `/preview/`, `/search`, `/t/`, `/poll/`, or query has `no-scripts`.

### 12.4 Fragment cache (Redis cache instance, tagged where needed)

| Fragment | TTL | Invalidated by |
|---|---|---|
| `settings:all` | until change | `SettingsSaved` |
| Menus per language | until change | `MenuSaved` / category/page changes |
| Category slug map, page slug map | until change | category/page save |
| Breaking ticker | 30 s | breaking change |
| Trending, most-read | 60–300 s | job rebuild |
| Related per article | 15 min | article change |
| Homepage block results | 60 s | homepage/article events |
| Script/ad output per (position, scope) | until change | script/slot save |

Laravel's `Cache::flexible()` (stale-while-revalidate) is used for fragments that are expensive to rebuild, to avoid stampedes.

### 12.5 Purge map

| Event | URLs / fragments purged |
|---|---|
| Article publish/update/unpublish/delete | article URL, primary and extra category pages (page 1–2), homepage, tag pages, author page, `feed.xml`, category feeds, `sitemap`/`news-sitemap`, related/ticker/trending fragments |
| Category/tag/author edit | their archive pages, menus, article URLs only if slug path changed (the 301 handles old ones) |
| Menu, settings, theme, script, ad slot change | **purge everything** (`CdnPurger::purgeAll()`) + clear local response cache |
| robots/ads.txt save | those URLs |

### 12.6 `CdnPurger`
```php
interface CdnPurger
{
    public function purge(array $urls): void;   // batched by driver limits, retried with backoff
    public function purgeAll(): void;
}
```
Drivers: `null`, `cloudflare` (URL purge + purge-everything; API token scoped to cache purge only). Purges are queued (`cdn` queue), **coalesced** (URLs collected into a Redis set and flushed by a delayed job after ~5 s to prevent purge storms on bulk edits). Failures are logged and shown on the system page; the TTLs above guarantee eventual consistency. Cloudflare tag-based purging is not assumed (plan-dependent).

### 12.7 Origin response cache (optional)
`PageCache` interface; default implementation wraps Spatie ResponseCache on the cache Redis instance, keyed by URL + language, skipped for any request with staff/session cookies. Enabled by config for installs without a CDN.

### 12.8 Cookie safety net
`PublicCacheHeaders` removes `Set-Cookie` from public responses and logs a warning (an Arch/feature test asserts public routes never emit cookies).

---

## 13. Scripts and Ads manager

### 13.1 Rendering
- `ScriptRenderer::for(Position $pos, PageContext $ctx): string` returns the concatenated active snippets matching the context, ordered by `priority`, cached per `(position, scopeKey)`.
- `PageContext` = `{type: home|article|category|tag|author|page|search|gallery|other, categoryIds, path, language}`. For an article, category scope matches the **primary** category (stated limitation).
- Path patterns use `Str::is()` wildcards; the exclude list wins over include.
- Theme layout **must** call `@cmsHead`, `@cmsBodyStart`, `@cmsBodyEnd` (enforced by `theme:check`).
- Not rendered: admin pages, preview URLs (for analytics/ads/pixel types), when `?no-scripts=1` + `cms_staff` (A3, response `no-store`), when `production_only` and `APP_ENV` ≠ production.

### 13.2 Ad slots
- Blade component `<x-ad-slot key="…"/>` outputs:
  ```html
  <div class="cms-ad" data-key="sidebar-top" data-lazy="1" style="min-height:250px"
       data-d="{base64 desktop}" data-m="{base64 mobile}"></div>
  ```
- `ad-loader.js`: chooses variant with `matchMedia('(min-width: 768px)')` (breakpoint configurable); on intersection (rootMargin 200 px) or immediately if not lazy, parses the chosen variant via a `<template>`, then **re-creates every `<script>` element** (copy attributes and text) so scripts execute, and appends the nodes. Only the chosen variant is ever injected (D9).
- `AdInserter` (render time): walks the article JSON, counts top-level paragraph nodes, and inserts slot nodes per `auto_insert` rules (`after_paragraph`, `repeat_every`, `max_per_article`, `min_paragraphs_required`). Never persisted.
- Min-height from slot config reserves space to protect CLS; empty variant → no element rendered for that device (CSS `display:none` via a data attribute).

### 13.3 Text files
`ads.txt`, `app-ads.txt`, `robots.txt` stored in `site_files`; controller serves `text/plain; charset=utf-8`; defaults generated for `robots.txt` (disallow admin path, `/search`, `/preview/`; list sitemaps). Reset-to-default action.

### 13.4 Revisions and rollback
`SaveScriptSnippet`/`SaveAdSlot`/`SaveSiteFile` actions write `config_revisions` (before/after) and an audit row inside the transaction; rollback is an Action that applies a revision and writes a new one.

### 13.5 Admin code editor
CodeMirror 6 (MIT) wrapped as the `CodeEditor` Livewire/Alpine component: HTML mode, no autocomplete, large-paste friendly.

---

## 14. Analytics

### 14.1 Beacon (`beacon.js`, ≤ 1.5 KB gzip)
Runs on article pages only. Skips if `cms_staff` cookie present, if `navigator.webdriver`, or if the page is a preview. After 2 s of `visibilityState === 'visible'` (timer pauses when hidden) sends once via `navigator.sendBeacon('/t/v', JSON)` (fallback `fetch` with `keepalive`). Payload: `{a: articleId, l: lang, r: referrerHost, w: viewportWidth}`.

### 14.2 Endpoint (`POST /t/v`, `cms.machine` group)
1. Validate payload shape and sizes; reject others with `204` (no oracle).
2. Resolve client IP from `CF-Connecting-IP` (only when the request comes from a trusted proxy range) else the socket IP.
3. Bot UA denylist check.
4. `visitor = sha256(salt(date) . ip . ua)` where `salt(date)` is a random value in Redis (`stats:salt:{YYYYMMDD in SITE_TIMEZONE}`, `SET NX`, TTL 36 h).
5. Dedupe: `SET stats:seen:{article}:{visitor} 1 NX EX {ANALYTICS_DEDUP_MINUTES*60}`; if it already existed → stop.
6. Pipeline: `INCR stats:d:{article}:{date}` (delta), `INCR stats:h:{article}:{YYYYMMDDHH}` (EX 48 h), `PFADD stats:hll:{article}:{date} {visitor}` (EX 48 h), `INCR stats:ref:{article}:{date}:{bucket}`, `INCR stats:dev:{article}:{date}:{bucket}`, `SADD stats:dirty {article}`, `ZADD stats:active {now} {article}`.
7. Return `204`.
Unknown or unpublished article ids are not checked per hit (no DB access on this path); they are skipped at flush time. Per-IP rate limit protects Redis memory.

### 14.3 Referrer and device buckets
Host lists in `config/analytics.php` (search, social, messaging). Messaging apps (WhatsApp etc.) often send no referrer and will land in `direct`; documented limitation. Device bucket: viewport < 768 → `mobile`.

### 14.4 Flush (`stats:flush`, every minute, `ShouldBeUnique`)
For each id from `SPOP stats:dirty` (batch of 500): `GETDEL` the delta counters; upsert into `article_stats_daily` with **additive** update (`views = views + ?`, referrer/device columns likewise); set `uniques = PFCOUNT(hll)` (overwrite, HLL is cumulative); `UPDATE articles SET total_views = total_views + ?`. Because deltas are consumed atomically with `GETDEL`, a crash loses at most one batch (documented ≤ 1 min loss; AOF `everysec` on the durable Redis).

### 14.5 Trending (`stats:trending`, every 5 min)
Candidates = `ZRANGEBYSCORE stats:active (now-24h) +inf`. For each, pipeline-read the last 24 hourly counters and compute `score = Σ views_h × 0.5^(age_hours / H)` (`H = TRENDING_HALF_LIFE_HOURS`, default 6). Write the top 50 per language to `trending:{lang}` (ZSET, replaced atomically via a temp key + `RENAME`). Prune `stats:active` entries older than 24 h.

### 14.6 Dashboard reads
SQL over `article_stats_daily` (indexed `(date, views)`) joined to `articles`; results cached 60 s; "today" and "by hour" blend the Redis hourly counters with the daily table. Reporter-scoped queries filter by `created_by`.

### 14.7 Capacity note
Beacon cost = framework boot + a Redis pipeline. At the PRD design target (100k page views/day, 10× spikes) this is well within PHP-FPM capacity on the reference server; spike S10 confirms at 100 rps and sets the threshold at which a slimmer front controller is justified.

---

## 15. Search

- Interface `ArticleSearch::search(string $q, string $lang, int $page): LengthAwarePaginator`; drivers `MysqlFulltextSearch`, `ScoutSearch`.
- **MySQL driver:** `MATCH(title, summary, body_text) AGAINST (? IN BOOLEAN MODE)` after escaping boolean operators; language filter; published scope; order by score then date.
- **Known risks (A9):** InnoDB `innodb_ft_min_token_size` defaults to 3 (hides short words); default parser may split Devanagari words at combining marks; the `ngram` parser (`WITH PARSER ngram`, `ngram_token_size` server variable) avoids word-splitting but changes relevance and index size and requires a server variable. Spike **S1** compares default, ngram, and Meilisearch on a labelled corpus; the result decides the default driver.
- **Meilisearch driver:** index per language `articles_{lang}`; searchable `title, subtitle, summary, body_text`; filterable `language, category_ids, published_at`; documents added/removed on article events via Scout queued jobs; `search:reindex` rebuilds.
- Query rules: min 2 chars, max 100, trimmed, rate-limited 30/min/IP, results `noindex`.

---

## 16. Queues, scheduler, health

### 16.1 Queues (durable Redis)

| Queue | Workers | Timeout | Used for |
|---|---|---|---|
| `default` | 2 | 60 s | listeners, notifications, search indexing |
| `media` | 1–2 | 300 s (512 MB) | conversions |
| `media-low` | 1 | 600 s | AVIF |
| `stats` | 1 | 120 s | flush, trending (unique jobs) |
| `cdn` | 1 | 60 s | purges (coalesced) |

Supervisor programs per queue; Horizon is optional and not required.

### 16.2 Job rules
Idempotent; explicit `tries`/`backoff`; `ShouldBeUnique` for periodic jobs; failures reported to Sentry with article/media ids; `failed_jobs` visible on the system page with retry.

### 16.3 Scheduler

| Task | Frequency | Notes |
|---|---|---|
| `articles:publish-scheduled` | every minute | `withoutOverlapping` |
| `stats:flush` | every minute | |
| `stats:trending` | every 5 min | |
| `breaking:expire` | every 5 min | |
| `system:heartbeat` | every minute | writes scheduler + per-queue heartbeat keys |
| `trash:purge`, `logs:prune`, `media:purge-deleted` | daily | |
| `media:report` | weekly | |
| `backup:run` | daily (off-peak) | `spatie/laravel-backup`; writes last-backup timestamp |

### 16.4 Health
The status page marks the scheduler or a queue **stale** when its heartbeat is older than 3 minutes. `/up` (Laravel health route) is used by uptime checks; A future `newscms:status --json` command may provide richer application-specific status if implemented under §16.5.

---

### 16.5 Application-specific Artisan commands

Use Laravel's native Artisan command system: implement a focused command as a normal Laravel console command (registered through the standard Laravel mechanism for the selected version). These are application-specific commands running inside Laravel, **not** a custom command framework.

Prefer Laravel's built-in commands first, including `php artisan migrate --force`, `php artisan db:seed` where appropriate, `php artisan config:cache`, `php artisan route:cache` where compatible, `php artisan view:cache`, `php artisan queue:restart`, `php artisan optimize:clear`, and `php artisan about`. Do not wrap these in a NewsCMS command unless the wrapper adds meaningful preflight checks, safe sequencing, or NewsCMS-specific validation.

Potential custom commands, implemented only when their associated feature is in scope:

| Command | Status / boundary |
|---|---|
| `newscms:status` | Useful candidate: aggregate app-specific readiness checks and support human-readable/JSON output. Keep `/up` as the simple Laravel health endpoint. |
| `newscms:sync-permissions` | Add only if deployment-time permission synchronization is not already handled safely by a seeder or migration. Must preserve administrator-customized role assignments. |
| `theme:check {name}` | Add with the theme system to validate metadata, required views/components, assets, and settings schema. |
| `search:reindex` | Add when the chosen search implementation needs an explicit rebuild command; use Scout/driver APIs where available. |
| `media:report` | Add if media operations need a safe diagnostic for missing files/orphaned records; never delete automatically. |
| `newscms:restore-test` | Defer until automated backup/restore infrastructure exists and can safely restore to an isolated temporary database. |
| `newscms:install` | Not a prerequisite for v1. Use documented Laravel setup commands and a secure first-admin procedure first. Add a guided installer only if real setup complexity later justifies it. |
| `newscms:upgrade` | Not required as a wrapper around standard migrations/cache/worker commands. A release checklist or deployment script is sufficient unless a tested application-specific sequence proves necessary. |
| `module:list`, `module:enable`, `module:disable` | Only if the product explicitly retains module toggling; no dynamic package installation, custom dependency graph, or data deletion on disable. |
| `stats:flush`, `stats:trending`, `csp:report` | Prefer scheduled Laravel jobs and existing status/configuration surfaces. Expose manual commands only where operators have a real use case. |

Never implement `newscms:engine`, `newscms:domain`, `newscms:bridge`, manifest/discovery commands, or a custom generator suite as a baseline requirement. Add narrow generators only after stable repeated work demonstrates a net benefit.

---


## 17. Module system

### 17.1 Registration model

Use Laravel service providers and explicit configuration to register first-party optional modules. Optional modules may live under `modules/` inside the application. Separately released first-party packages are not part of v1 and should be considered only if a demonstrated need emerges. Do not build a dynamic package-discovery runtime or dependency graph.

Each module documents its provider, compatibility requirements, routes, migrations, permissions, settings, scheduled work, and owned tables. Register providers through the normal Laravel package/provider configuration path. A simple `module:list` command may report the declared modules and their status.

### 17.2 Enablement and lifecycle

If the product retains runtime module toggling, the enabled set is explicit and cached through Laravel config/cache facilities. Enabling runs reviewed migrations and permission synchronization; disabling removes the module's routes and UI from active use but preserves its data. Code installation and upgrades happen through Composer/deployment, never through the admin.

Avoid runtime topological sorting, registry freezing, manifest graph rebuilds, and automatic migration rollback. Dependencies must be few, explicit, and validated by tests. If a module requires another optional module, fail with a clear message when the requirement is not met.

### 17.3 Extension API

Prefer normal Laravel extension points: service providers, routes, policies, events/listeners, Blade components, view namespaces, configuration, and documented interfaces. Add a focused registry only when several real consumers need runtime registration (for example homepage block types or editor block renderers). Do not introduce a universal registry/hook framework pre-emptively.

Only documented extension points are public API. Module code must not bypass authorization, directly write tables owned by another capability, or alter another capability’s migrations. Use architecture tests to enforce concrete rules.

### 17.4 Acceptance

A first-party optional module has tests for registration, permissions, route/UI visibility, owned migrations, enable/disable behaviour where supported, and data preservation. The main editorial workflow and public pages continue to work when that module is disabled.

---

## 18. Theming contract

NewsCMS implements its theme system using Laravel Blade and view resolution. Do not introduce Twig, a third-party CMS theme engine, or a second template language.

- Theme location: `themes/{name}/` with `theme.json`, `views/`, `assets/`, and optional `lang/` resources. The application ships a default theme and may include additional project-owned themes.
- `theme.json` declares `name`, `version`, supported languages, typed admin-exposed options, and required template mappings. Validate it as data; never execute PHP from theme metadata.
- Active theme and options are per install. Validate option types and allowed values, cache resolved settings, and provide safe defaults when a theme or setting is missing/invalid.
- Resolution order: active theme view → default theme view → explicit error/fallback view. Resolution must be deterministic and covered by tests.
- Required templates: home, article, category, tag, author, search, page, 404, 410, and error; live article and gallery templates are required only when those modules are enabled.
- Stable components/slots include `x-picture`, `x-ad-slot`, `x-breaking-ticker`, `x-trending`, `x-related`, `x-pagination`, `x-lang-switcher`, `x-share`, and `x-embed`. Theme layouts include the documented head/body hooks required for SEO metadata, safe script insertion, and ad integrations.
- Views receive prepared data through controllers/query services or small typed view models. Themes must not query models directly, perform authorization, or implement publishing rules.
- Assets are built with Vite. `theme_asset()` resolves the theme asset manifest; production servers serve built assets and do not need Node.js. CSS design tokens use custom properties derived from validated/cached theme settings.
- `theme:check {name}` validates metadata, required views/components, assets, and option schemas. It should report actionable errors.
- Accessibility, responsive behaviour, RTL logical properties, Indic typography, and the default-theme performance budget are tested. Child themes, marketplace distribution, drag-and-drop page building, and arbitrary executable theme extensions are out of scope for v1.

---

## 19. Security implementation

| Area | Implementation |
|---|---|
| XSS | Schema-validated JSON renderer (§8). `{!! !!}` is banned in views except inside one component (`x-article-body`) — a CI grep check enforces it. Symfony HtmlSanitizer for raw HTML paths (§8.6) |
| Uploads | `finfo` MIME check, extension allowlist, random paths, dimension/megapixel guard, ImageMagick `policy.xml` hardening (§10.2), no executable handler on upload dirs (Nginx `location` deny for `*.php` there) |
| SSRF | No server-side fetch of user-supplied URLs in v1 (embeds are parse-only) |
| CSRF | Standard on admin; machine endpoints are CSRF-exempt but `OriginCheck` (Origin/Referer host must match site hosts) + rate limits |
| Rate limits | login 5/min per email+IP; password reset 3/hour; upload 60/min per user; search 30/min per IP; beacon 120/min per IP; poll vote 10/min per IP; live entries 60/min per IP |
| Headers | HSTS (when HTTPS enforced), `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `X-Frame-Options: SAMEORIGIN`, minimal `Permissions-Policy`; CSP optional per install; embed adapters expose their hosts for CSP generation (`newscms:csp`) |
| Client IP | `TrustProxies` configured with Cloudflare ranges; `CF-Connecting-IP` honoured only from those ranges (rate limits and analytics depend on it) |
| Secrets | `.env` mode 640, owned by the deploy user; Cloudflare token limited to cache purge; no secrets stored in the DB |
| Scripts manager | Admin-only permission, audit + revisions, safe mode; documented as intentional raw-code surface |
| Backups | Encrypted at rest (spatie backup password or storage-side encryption) |
| Supply chain | `composer audit` + `npm audit` fail CI on high severity; Renovate/Dependabot for security updates; licence audit (§2) |
| Logging/PII | No raw IPs in analytics; IPs only in audit log and rate-limit keys; Sentry configured to scrub request bodies and cookies |
| Admin discovery | `ADMIN_PATH` is a convenience, not a control; real controls are auth + 2FA + rate limits |

---

## 20. Performance engineering

### 20.1 Budgets (initial, tunable; enforced by Lighthouse CI on the default theme)

| Metric | Budget |
|---|---|
| Public JS (CMS application, excluding third-party) | ≤ 20 KB gzip total (beacon ≤ 1.5, embed-loader ≤ 3) |
| Public CSS (default theme) | ≤ 40 KB gzip |
| Fonts | ≤ 2 weights per script, ≤ 60 KB WOFF2 each (subsetted) |
| Article first view (no ads/embeds) | ≤ 500 KB transferred; LCP image ≤ 150 KB |
| Origin article render (cache miss, warm fragments) | ≤ 15 queries, TTFB < 500 ms on reference server |
| Admin list pages | ≤ 12 queries, p95 < 1.5 s at 10k articles |

### 20.2 Practices
- Query-count assertions in feature tests for the hot pages; `shouldBeStrict` in non-production.
- LCP image: `fetchpriority="high"`, no `loading=lazy`, explicit width/height; others lazy.
- PHP-FPM `pm = dynamic` sized from RAM (≈ 60% of RAM ÷ ~60 MB per worker, tuned in pilot); OPcache `validate_timestamps=0` with FPM reload on deploy; JIT off by default.
- Brotli at the CDN, gzip at origin; HTTP/2+ at the edge; hashed assets immutable.
- Third-party scripts (§13) are the main CWV risk: defaults `async`/`defer` guidance in admin help text; the scripts screen warns above N snippets.

---

## 21. Infrastructure and deployment

### 21.1 Reference deployment topology
One NewsCMS application deployment: Nginx, PHP-FPM, MySQL, Redis/Valkey for cache and queues (separate instances where capacity permits), Supervisor workers, optional Meilisearch. Media may use local disk or S3-compatible storage. A CDN such as Cloudflare is optional.

### 21.2 Baseline production configuration
- **Nginx:** `client_max_body_size` from `MAX_UPLOAD_*`; gzip on; deny dotfiles; deny PHP execution under storage/upload paths; `try_files` to `index.php`; long cache for hashed assets; real-IP module configured for Cloudflare ranges.
- **PHP:** `upload_max_filesize`/`post_max_size` aligned with Nginx, `memory_limit` 256M (media worker 512M), OPcache enabled, `expose_php=Off`.
- **MySQL:** `utf8mb4`; `innodb_buffer_pool_size` ≈ 25–35% of RAM on a shared box (≈ 50–60% if dedicated); slow query log at 1 s; ngram settings if S1 selects it.
- **Redis:** as in §21.1; `maxmemory` set per instance.

### 21.3 Environments
Local development should follow the repository setup guide and must not require Docker Compose unless explicitly adopted as a project decision. Staging should mirror the intended production stack and use a seeded content corpus. Production runs the application as one site deployment.

### 21.4 Release and deployment process
1. Pull request runs the full CI pipeline (§23).
2. A reviewed release is tagged and documented.
3. Deploy the application using the chosen hosting workflow: install dependencies, build/publish assets through the agreed process, run backward-compatible migrations, rebuild Laravel caches, restart workers, and run health checks.
4. Take or verify a recent backup before schema changes; define a rollback or forward-fix procedure for each release.
5. Keep production secrets outside source control. Avoid requiring a custom fleet system or per-customer deployment inventory.

### 21.6 Backups and restore drills
- Database: daily dump via `spatie/laravel-backup` to a remote bucket; retention 14 daily + 8 weekly; encrypted.
- Media: provider-side versioning/replication where available; otherwise nightly `rclone sync` to a second bucket with delete-protection window.
- Once backup/restore automation is implemented, a restore-test command or scheduled job may restore a dump into an isolated temporary database and assert integrity; do not schedule it until safe isolation and resource limits are tested.

---

## 22. Observability

- Logs: JSON to `stderr`/daily files, request-id middleware (also returned as `X-Request-Id`), slow request log (> 2 s), MySQL slow query log.
- Errors: Sentry (low trace sample rate, PII scrubbing).
- Uptime: external check on `/up` and one article URL; alert on failure or latency regression.
- Alerts (to the team channel): queue/scheduler stale > 3 min; failed jobs > 20 in 10 min; disk > 80%; DB size growth anomaly; last backup > 26 h; CDN purge failures > 5/10 min; certificate expiry < 14 d.
- Status page (PRD §21.3) is the human view of the same signals.

---

## 23. Testing and CI

### 23.1 Pipeline stages
1. Pint (style) → 2. Larastan level 6 → 3. Pest unit + feature on **MySQL and Redis service containers** (avoid SQLite as the authoritative integration-test database: full-text, JSON, and locking behaviour differ) → 4. Arch tests → 5. Frontend build + Vitest (including block-schema parity, embed-loader, ad-loader) → 6. licence + security audits → 7. `theme:check` for shipped themes → 8. Lighthouse CI on the default theme → 9. **nightly:** Playwright browser suite, migration-on-large-seed smoke, k6 load profile.

### 23.2 Required automated tests (maps to PRD §24.1)

| Area | Examples |
|---|---|
| Permissions | generated matrix test over every admin route and Livewire action |
| Workflow | state machine transitions, scheduled publish at the minute, reject-with-note |
| URLs | resolver order, id-based lookup, 301 on slug/category change, 410 vs 404, reserved slugs |
| Slugs | Latin and Devanagari corpus, collisions, unicode option |
| Revisions/conflict | autosave isolation, `row_version` conflict path |
| Media | dedupe, MIME spoof rejection, megapixel guard, conversion jobs, focal crop math |
| Redirects | exact/wildcard, loop detection, CSV import validation |
| Scripts/ads | scope matching, device variant (only one injected), script re-creation, safe mode, rollback |
| Analytics | dedupe window, bot filter, flush additivity (run twice, same totals), trending decay |
| Sitemaps/feeds | inclusion/exclusion, 48 h news window |
| Cache | purge map per event, public routes emit no cookies |
| Security | XSS vector corpus through schema renderer and sanitizer, upload attacks, rate limits |
| Arch | module boundaries are respected; public queries use `published()`; no `{!! !!}` outside `x-article-body`; public routes bypass session |

### 23.3 Fixtures and load tests
- `CorpusSeeder`: 10k articles (mixed Hindi/English, varied lengths, tags, categories, media rows) for search spikes, query-count tests, and performance.
- k6 scenarios: cached article read, homepage, beacon at 50→100 rps, admin save, bulk media upload; thresholds on p95 latency and error rate, tuned during Phase 5.
- Browser tests (at least one per phase): create → submit → approve → scheduled publish visible; embed click-to-load; ad slot injection by viewport; live blog polling.

---

## 24. Coding standards and process

- `declare(strict_types=1)` everywhere; Laravel Pint (Laravel preset); final classes by default; constructor injection; no facades inside Domain Actions except `DB`/`Event`.
- Naming: Actions are verbs (`PublishArticle`), Queries are nouns (`LatestArticlesQuery`), enums singular, Blade components kebab-case.
- Livewire components stay under ~150 lines of PHP; logic moves to Actions.
- Git: trunk-based, short-lived branches, required reviews, squash merge, Conventional Commits; CODEOWNERS for documented extension contracts and migrations.
- PR template embeds the PRD §24.3 Definition of Done checklist.
- Decisions: ADRs in `docs/adr/NNNN-title.md` (short MADR format). Any change that touches the PRD or this TRD includes the edit in the same PR.
- Project documentation: admin guide, theme guide, module notes where needed, operations runbook, and application upgrade notes.

---

## 25. Versioning, compatibility, deprecation

- Semver. **Public API** = documented interfaces/events, config keys, Blade component contracts, `theme.json` schema, supported module metadata, and documented custom Artisan command names/options, if any are released. Everything else is internal.
- Minor releases: additive only (new features, additive migrations, new events). Deprecations emit `E_USER_DEPRECATED` and log once per process; removal only in the next major, at least one minor later.
- Laravel/PHP upgrades: verify compatibility, run the full test suite, document breaking changes, and deploy through the application release process.
- Version application releases clearly and document schema/configuration changes. Prefer additive migrations; document breaking changes and recovery steps. Laravel/PHP upgrades require compatibility checks and the full test suite. No separate Core versioning or fleet rollout policy is needed for this single application.

---

## 26. Technical spikes and open decisions

| ID | Question | Phase | Method | Decision rule | Fallback |
|---|---|---|---|---|---|
| S1 | Devanagari search quality: InnoDB default vs ngram vs Meilisearch | Phase 2 | Labelled corpus (30+ real queries) from `CorpusSeeder` | Pick the lowest-complexity option that finds the expected article in the top 10 for ≥ 90% of queries and never splits a word incorrectly | Meilisearch becomes the default driver |
| S2 | Slug transliteration quality | Phase 2 | 200 real Hindi headlines through ICU and `portable-ascii` | Readable and low collision rate by editor review | Default to `UNICODE_SLUGS=true` for Hindi installs |
| S3 | Livewire update/upload/script routes under the admin prefix | Phase 0 | Prototype with cookie path `/{ADMIN_PATH}` | All admin features work with scoped cookie | Cookie path `/` + CDN bypass on `cms_session` |
| S4 | Large-file upload strategy | Phase 2 | Prototype Uppy+tus vs XHR chunking at 200 MB over throttled network | Resumable, passes Nginx/PHP limits without raising them globally | Embeds-only for video (PRD Q7) |
| S5 | Paste fidelity and defaults | Phase 2 | Fixture set: Word, Google Docs, news websites, WhatsApp text | Default mode preserves structure + inline marks with zero style leakage; `keep-styles` mode renders safely | Narrow the default allowlist |
| S6 | CDN purge limits and behaviour (batch size, rate, propagation time) | Phase 3 | Cloudflare sandbox zone | Purge of a publish event completes < 10 s end-to-end | Shorter `s-maxage` on listings |
| S7 | Live behaviour of each embed provider's markup/script after dynamic insertion | Phase 2 (pre-check), Phase 4 (final) | Page harness that inserts markup then loads script | Each provider renders after click on Chrome/Safari/Firefox | Provider-specific re-insertion or a link-only fallback |
| S8 | Impact of ad/tracker scripts on Core Web Vitals in the default theme | Phase 4 | Lighthouse with GA4 + AdSense-like loader | Document measured deltas; set warning thresholds | Recommend lazy slot settings by default |
| S9 | Imagick AVIF support across target servers | Phase 2 | Matrix of distro images | WebP-only when unsupported, detected at install | libvips path |
| S10 | Beacon throughput and boot cost on the reference server | Phase 5 | k6 at 100 rps | p95 < 100 ms, CPU < 60% | Slim front controller for `/t/v` |

---

## 27. Traceability (PRD → TRD)

| PRD section | TRD section |
|---|---|
| §3 Decision log (D1–D14) | §1, §3, §5, §8, §9, §12, §13 |
| §4 Roles/permissions | §7 |
| §5 System requirements | §2, §16, §21 |
| §6.1 Articles | §4, §6.4, §8 |
| §6.2–6.7 Taxonomy, pages, menus, homepage | §5.3, §6.3, §12.4, §18 |
| §7 Media | §10 |
| §8 Languages, URLs, slugs | §5, §11 |
| §9 News features (embeds, live blog, galleries, polls) | §9, §17 (modules), §12.2 (`/live`) |
| §10 Scripts and Ads | §13 |
| §11 Analytics | §14 |
| §12 Module system | §17 |
| §13 SEO, feeds, redirects | §5.3, §12 |
| §14 Search | §15 |
| §15 Admin panel | §7, §8.4, §24 |
| §16 Audit log | §4.3, §6.8 |
| §17 Theming | §18 |
| §18 Performance, caching | §12, §20 |
| §19 Security and privacy | §19 |
| §20 Data model | §6 (conventions); schema in PRD §20 plus A1, A6 |
| §21 Operations and install | §16, §21, §22 |
| §23 Delivery plan | §26 (spikes mapped to phases) |
| §24 Quality | §23, §24 |

---

## 28. Technical risks

| Risk | Impact | Mitigation |
|---|---|---|
| Livewire route relocation not possible (S3) | Session cookie must be path `/`; more CDN bypass traffic for staff | Fallback designed; staff traffic is tiny |
| Devanagari search poor with MySQL (S1) | Bad search UX at launch | Meilisearch path ready behind the driver interface |
| Provider embed markup/script changes | Broken embeds on live sites | Adapter isolation, smoke tests, link-only fallback, quick-fix application release path |
| Purge storms or CDN API limits | Stale pages or throttled API | Coalescing, TTL fallback, purge-all only for global config |
| Redis eviction/loss | Lost jobs or counters | Two instances, AOF, documented ≤ 1 min stats loss |
| Image processing exhausting memory | Worker crashes | Pixel guards, worker memory limits, `policy.xml`, retries |
| Environment drift (config, PHP, Redis) | Unreproducible bugs | Documented environment requirements and Laravel health checks, with an application status command only if justified |
| Block schema drift between JS and PHP | Silent content corruption | Parity test in CI, strict validation (reject, never ignore) |
| Livewire editor sync bugs (lost edits) | Data loss | Explicit sync before save, autosave revisions, conflict detection (A1) |
| Unclear deployment assumptions | Operational failures | Document one supported deployment path and test it before launch |