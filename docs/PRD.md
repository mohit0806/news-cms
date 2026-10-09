# PRD — NewsCMS (working name)

**Product:** A straightforward, maintainable Laravel-based News CMS for running a news publication website.
**Document status:** Draft v1.4 — product scope corrected; operational requirements separated from implementation details.
**Updated:** 2026-10-09.
**Purpose:** This PRD defines product behaviour and acceptance criteria; implementation details belong in `TRD.md`.
**Rule of this document:** If something is not written here, it is not in scope. If something is written here, build it exactly as written or raise the question before building.

Items marked **[ASSUMPTION]** are not yet confirmed and must be confirmed before the phase that depends on them starts (list in §22).

---

## 1. Product overview

### 1.1 Vision
A maintainable News CMS application that lets a newsroom create, manage, publish, and present news through a secure admin panel and a public website.

### 1.2 Goals
1. Provide a complete editorial workflow for creating, reviewing, scheduling, and publishing news.
2. Handle a realistic newsroom media library and traffic spikes on breaking stories.
3. Support regional-language content, with Hindi and English requirements confirmed before implementation.
4. Let authorized administrators manage site settings, theme presentation, and approved scripts without editing application code.
5. Keep the codebase modular, testable, secure, and straightforward to maintain.
6. Provide a useful default public theme that can be customized through the CMS theme system.

### 1.3 Non-goals (explicitly out of scope for v1)
Paid/sponsored content labelling, paywall/membership, comments, e-paper, push notifications, social auto-posting, multi-tenancy, plugin marketplace, contact-form builder, newsletter, article edit-locking, a built-in consent banner, header-bidding/ad-server configuration, ad performance reporting, AMP, mobile apps, a public API. Any of these can be added later as modules (§12) or a later PRD version.

### 1.4 Intended use
A single NewsCMS application for one news publication/site at a time. Multi-tenant hosting, commercial customer provisioning, a fleet of customer installations, and a product licensing/billing system are not part of this PRD.

### 1.5 Success criteria (pilot)
- The newsroom can create, review, publish, edit, and schedule real articles for four weeks without data loss or a blocking bug.
- The default theme targets Core Web Vitals "good" thresholds on article pages (LCP ≤ 2.5 s, INP ≤ 200 ms, CLS ≤ 0.1) with no third-party scripts, on a mid-range mobile profile.
- A fresh local/staging setup reaches a working admin login using documented setup instructions.
- Backups can be restored successfully, and a release can be deployed using a documented repeatable process.

---

## 2. Glossary

| Term | Meaning |
|---|---|
| NewsCMS application | The Laravel application containing the CMS, admin portal, public site, and its configuration. |
| Module | An optional first-party feature integrated through Laravel service providers and the documented NewsCMS extension points (§12). |
| Theme | A folder of Blade views, assets, and typed presentation settings for the public site. |
| Slot (ad slot) | A named place in a theme/article where ad code is rendered. |
| Snippet | An admin-managed script/code fragment injected into pages (§10). |
| Language version | A single article/page in one language. |
| Translation group | A UUID linking language versions of the same story. |
| Beacon | A tiny JS request used to count article views (§11). |

---

## 3. Decision log (do not re-litigate without updating this table)

| # | Decision | Reason |
|---|---|---|
| D1 | One application instance serves one news publication/site in v1; no multi-tenancy | Keep the first version simple and operationally understandable |
| D2 | Build and maintain a normal Laravel application; do not split it into a Core package and customer starter app | Avoid distribution and release machinery that the product does not currently require |
| D3 | Custom admin built with Livewire, Blade, Alpine where useful, and Flux UI components | Custom newsroom workflows remain under our control; Flux is a UI component layer, not a complete admin system (§15) |
| D4 | Media disk configurable through the application environment | Supports local or object storage without hard-coding infrastructure |
| D5 | Each language version of an article/page is its own row, linked by `translation_group_id` | News translations are independent stories |
| D6 | Categories and tags use **translation tables** (not JSON columns) | Needs unique slugs per language. *Supersedes the architecture doc.* |
| D7 | Article URL = `/{category-slug}/{article-slug}-{id}`; the **id** is the lookup key | Slug or category changes never break links; mismatches 301 to canonical |
| D8 | View counting uses a JS beacon, not server-side counting | Full-page caching means PHP never sees cached hits |
| D9 | Ad-slot device targeting is client-side, only the matching variant is injected | Avoids double-loading ads and keeps full-page cache valid |
| D10 | Embeds use each provider's **official embed markup + script**, loaded only on click; no hand-rolled iframes, no self-rendered post cards (YouTube is the one exception: its official embed is the player iframe, also loaded on click) | Page speed, privacy, native platform look, no site-specific API tokens |
| D11 | Modules are first-party only | Security and support cost |
| D12 | Public pages never set cookies | Required for CDN/full-page caching |
| D13 | No edit-locking; use optimistic concurrency (conflict warning) | Cheaper, avoids stuck locks, still prevents silent overwrite |
| D14 | TipTap block-based editor storing JSON, rendered to HTML on save (confirmed 2026-10-09, P0-T05) | In-article ads, embeds, galleries, polls need structure |

---

### 3.1 Product architecture principles

NewsCMS is a **single Laravel modular-monolith application** for managing one news publication/site in v1. Laravel remains the application framework. NewsCMS adds news-specific capabilities and a small set of enforceable conventions; it does not create a second runtime framework or a commercial distribution platform.

#### 3.1.1 Capability boundaries

The codebase is organised around cohesive capabilities. “Engine” may be used as a conceptual label for a capability boundary, but it does **not** imply a separate Composer package, custom runtime, standalone framework, or independent service.

Core capability areas include:

- Platform: settings, identity/access, audit, revisions, notifications, health, localization, caching, operations.
- News content: articles, authors/bylines, categories, tags, pages, menus, homepage composition, publishing workflow.
- Publishing support: media, editor blocks, embeds, URLs/routing, search, discovery, navigation, SEO/feeds/redirects.
- Presentation and growth: theme resolution, layouts/widgets/slots, scripts and ads, first-party analytics.
- Optional features: LiveBlog, Galleries, and Polls, subject to the v1 scope decisions in §12 and §22.

These areas should have clear ownership of their models, policies, actions, queries, and migrations. Cross-capability collaboration uses ordinary Laravel mechanisms—interfaces where they remove real coupling, Laravel events/listeners for side effects, and explicit application services. Avoid a Bridge package or custom event/DI/command framework when a normal class or Laravel feature is sufficient.

#### 3.1.2 Boundary enforcement

Use architecture tests and static analysis to prevent demonstrable problems: forbidden dependency directions, circular dependencies, business logic in presentation layers, and writes to another capability's tables. Add a rule only when it protects a real boundary. Do not require manifests, registries, generated dependency graphs, package-per-capability layouts, or standalone test harnesses for every capability.

#### 3.1.3 Media source of truth

Spatie Media Library is the source of truth for media records and attachments. Content tables MUST NOT add direct media foreign-key columns merely to point at an image. Featured images, category images, author photos, OG images, and editor-inserted media are attached through named media collections and the Media Library APIs. A database relationship column may be added only when a separate product relationship—not merely a media attachment—requires it.

---

## 4. Users and roles

### 4.1 Roles (seeded; admins may create more roles from permission sets)

| Role | Purpose |
|---|---|
| Admin | Full control of the site, users, settings, scripts & ads, modules |
| Editor | Manages and publishes all content; no users/settings/scripts |
| Reporter | Writes own articles, submits for review; cannot publish |
| Contributor | Writes drafts only, no media management beyond own uploads; cannot submit for publish except via Editor action (same as Reporter minus category/tag creation; confirmed 2026-10-09, P0-T05) |

Each user has exactly one role. Roles are sets of permissions; permission keys are fixed in code.

### 4.2 Permission matrix (✔ = granted by default to the seeded role)

| Permission key | Admin | Editor | Reporter | Contributor |
|---|---|---|---|---|
| `articles.create` | ✔ | ✔ | ✔ | ✔ |
| `articles.edit.own` | ✔ | ✔ | ✔ | ✔ |
| `articles.edit.any` | ✔ | ✔ | | |
| `articles.submit` | ✔ | ✔ | ✔ | ✔ |
| `articles.publish` | ✔ | ✔ | | |
| `articles.unpublish` | ✔ | ✔ | | |
| `articles.delete` | ✔ | ✔ | | |
| `articles.set_breaking` | ✔ | ✔ | | |
| `blocks.raw_html` | ✔ | ✔ | | |
| `categories.manage` | ✔ | ✔ | | |
| `tags.manage` | ✔ | ✔ | ✔ (create only) | |
| `authors.manage` | ✔ | ✔ | | |
| `media.upload` | ✔ | ✔ | ✔ | ✔ |
| `media.manage` (edit/delete any) | ✔ | ✔ | | |
| `galleries.manage` | ✔ | ✔ | | |
| `polls.manage` | ✔ | ✔ | | |
| `live.manage` | ✔ | ✔ | ✔ (own live blogs) | |
| `pages.manage` | ✔ | ✔ | | |
| `menus.manage` | ✔ | ✔ | | |
| `homepage.manage` | ✔ | ✔ | | |
| `redirects.manage` | ✔ | ✔ | | |
| `analytics.view` | ✔ | ✔ | own articles only | |
| `scripts.manage` | ✔ | | | |
| `users.manage` | ✔ | | | |
| `roles.manage` | ✔ | | | |
| `settings.manage` | ✔ | | | |
| `modules.manage` | ✔ | | | |
| `audit.view` | ✔ | | | |
| `system.view` (health page) | ✔ | | | |

Acceptance: every admin route and Livewire action is guarded by a policy or permission check; a test exists that asserts each permission denies access when absent.

---

## 5. System and deployment requirements

### 5.1 Stack (pin exact versions in `composer.json`; confirm current stable at project start)
- PHP 8.3+, a supported Laravel release, compatible Livewire and Flux UI releases, Alpine.js where needed, and Tailwind CSS. Pin exact compatible versions at implementation kickoff.
- MySQL 8.0+ (`utf8mb4`). Redis/Valkey for cache, queues, sessions, and counters; separate cache and durable instances are preferred (see TRD §2).
- Image processing: Intervention Image with Imagick (or libvips if installed). WebP required; AVIF optional; setup/environment checks verify support and the application falls back to WebP only when AVIF is unavailable.
- Editor: TipTap with custom block nodes, server-side rendering to HTML **[ASSUMPTION]**.
- Search: MySQL fulltext first, Laravel Scout + Meilisearch behind `SEARCH_DRIVER`.
- Tests: Pest or PHPUnit; static analysis: Larastan; style: Laravel Pint.

### 5.2 Reference server (to validate by load test in Phase 5)
2 vCPU, 4 GB RAM, SSD/NVMe, Redis on same host, CDN (Cloudflare or equivalent) in front. Design target (**[ASSUMPTION]**, to validate): 100k page views/day with 10x spikes served mainly from CDN/full-page cache.

### 5.3 Processes required on every install
Web (PHP-FPM + Nginx), queue worker(s) under Supervisor or Horizon, scheduler (`schedule:run` every minute), Redis, MySQL.

---

## 6. Content domain

### 6.1 Articles

#### 6.1.1 Types
- `standard`: normal article.
- `live`: live blog (see §9.5). Same fields, plus entries.

#### 6.1.2 Fields

| Field | Type | Required | Rules |
|---|---|---|---|
| `language` | code (e.g. `hi`, `en`) | yes | Must be in `SITE_LANGUAGES` |
| `translation_group_id` | UUID | no | Links language versions |
| `title` | string ≤ 200 | yes | |
| `subtitle` | string ≤ 250 | no | Shown as standfirst/kicker |
| `slug` | string ≤ 190 | yes | Auto-generated from title (see §8.3), editable, unique per language |
| `summary` | text ≤ 400 | recommended | Used for cards, meta fallback |
| `body_json` | JSON | yes to publish | Block content |
| `body_html` | longtext | system | Rendered and sanitized on save |
| `body_text` | longtext | system | Plain text for search |
| `featured image/media` | Spatie Media Library collection | required to publish (setting `require_featured_image`, default on) | |
| `featured_caption`, `featured_credit` | string | no | Override media defaults |
| `primary_category_id` | FK | yes | Defines URL category |
| additional categories | many-to-many | no | Max 3 extra |
| tags | many-to-many | no | Max 15 |
| `author_id` | FK authors | yes | Primary byline |
| co-authors | many-to-many ordered | no | Max 5 |
| `dateline` | string ≤ 100 | no | e.g. "Jaipur" |
| `source` | string ≤ 100 | no | e.g. agency name |
| `status` | enum | yes | See §6.1.3 |
| `published_at` | datetime | yes to publish | Future date = scheduled |
| `is_breaking` | bool | no | Needs `articles.set_breaking` |
| `breaking_until` | datetime | no | Defaults to now + `BREAKING_DEFAULT_HOURS` |
| `manual related` | ordered list of article ids | no | Max 6; overrides auto-related when present |
| SEO: `meta_title` (≤ 70), `meta_description` (≤ 170), `canonical_url`, `default OG media`, `allow_indexing` | | no | Fallbacks: title, summary, featured image, indexing on |
| `reading_time_min` | int | system | words / 200, min 1 |
| `total_views` | bigint | system | Updated by analytics flush |
| `created_by`, `updated_by`, timestamps, soft delete | | system | |

Validation to publish (hard block with clear messages): title, slug, body not empty, primary category, author, featured image (if setting on), alt text present on featured image (if setting `require_alt_text` on, default on), `published_at` set.

#### 6.1.3 Statuses and transitions

| From → To | Who | Notes |
|---|---|---|
| `draft` → `in_review` | anyone with `articles.submit` | Notifies editors |
| `in_review` → `draft` | Editor/Admin | Requires a rejection note; notifies author |
| `draft` / `in_review` → `published` | `articles.publish` | If `published_at` is in the future, status becomes `scheduled` |
| `scheduled` → `published` | Scheduler (every minute) | When `published_at ≤ now` |
| `scheduled` → `draft` | `articles.unpublish` | Cancels schedule |
| `published` → `unpublished` | `articles.unpublish` | URL returns 410 unless a redirect exists |
| `unpublished` → `published` | `articles.publish` | |
| any → `trashed` (soft delete) | `articles.delete` | Restorable for 30 days then purged (setting) |

Reporters can edit their own article until it is `published`; after that only Editors/Admins can edit. Edits to a `published` article by an Editor are live immediately and create a revision.

#### 6.1.4 Revisions and autosave
- Autosave to a single `autosave` revision every 30 s while editing (never changes the live article).
- A `manual` revision is stored on every explicit save/publish. Keep last 50 manual revisions per article.
- Revision view: list with user and time, side-by-side text diff of title, subtitle, summary, and body text, one-click restore (creates a new revision).
- **Conflict detection (D13):** every edit form carries the article's `updated_at` loaded at open time. On save, if the stored value differs, block the save and show "Changed by {user} at {time}" with options: view their version / overwrite / copy my text.

#### 6.1.5 Preview
- "Preview" generates a signed URL valid 24 h for any status. Preview pages send `noindex` and are never cached or counted in analytics.

#### 6.1.6 Editor blocks (v1)
paragraph, heading (h2–h4), blockquote/pull-quote (with attribution), bullet/numbered list, image (media picker; per-block alt, caption, credit override; alignment), gallery (pick a gallery), embed (URL or pasted official embed code from YouTube / X / Instagram / Threads / Facebook / TikTok, see §9.4), uploaded video/audio file (no transcoding; size limits per §7.2), simple table, divider, inline related-article card, poll (pick a poll), raw HTML (requires `blocks.raw_html`; sanitized by allowlist; **no `<script>`, no inline event handlers, iframes only from the embed allowlist**).
- Pasted content is cleaned (strip styles/classes/Word markup). Pasted images are rejected with a message to upload through the media picker.
- Body is validated server-side; unknown block types are rejected.

#### 6.1.7 Acceptance (articles)
- A reporter can create, autosave, submit; an editor sees it in "Pending review", approves with a future date; it appears publicly at exactly that time (±1 min) and not before.
- Changing the primary category or slug after publishing keeps the old URL working via 301 to the new one.
- Two editors saving the same article never silently overwrite each other.

### 6.2 Categories
- Nesting up to 3 levels. Fields: `parent_id`, `sort`, `color` (hex, theme accent), category image/media attachment, `show_in_menu`, `is_active`. The image is stored through the Spatie Media Library collection; no `image_media_id` column is required.
- Translation table `category_translations`: `language`, `name`, `slug`, `description`, `meta_title`, `meta_description`. Unique `(language, slug)`.
- A category with articles cannot be deleted; admin must reassign (bulk move tool provided).
- Public listing `/{category-slug}` (and `/{parent}/{child}` for nested), paginated, newest first.

### 6.3 Tags
- Translation table `tag_translations` (`language`, `name`, `slug`). Unique `(language, slug)`.
- Tag merge tool (merge A into B: re-points articles, creates redirect for A's archive URL).
- Public archive `/tag/{slug}`, paginated. Tag archives are `noindex` when they have fewer than `TAG_INDEX_MIN_ARTICLES` (default 5).

### 6.4 Authors (bylines)
- Separate from users. Fields: `name`, `slug` (unique), `bio`, `photo/media attachment`, `email` (private, never shown publicly), `social_json`, `is_active`, optional `user_id`.
- Public page `/author/{slug}` with bio and their articles, paginated.
- An author with articles cannot be deleted; can be deactivated.

### 6.5 Static pages
- Fields: `language`, `translation_group_id`, `title`, `slug`, `body_json/html`, `template` (`default`, `full-width`), `status` (`draft`, `published`), SEO fields.
- Public URL `/{slug}`. Slug must be globally unique across pages **and** category slugs **and** reserved words (see §8.2).

### 6.6 Menus
- Menu = `name`, `location` (`header`, `footer`, `mobile`), `language`.
- Items: `label`, `type` (`category`, `page`, `article`, `tag`, `url`), target id or URL, `open_new_tab`, `parent_id` (max 2 levels), `sort`, `is_active`. Drag-and-drop reorder.
- If a linked category/page is deleted or unpublished, the item is hidden automatically and flagged in admin.

### 6.7 Homepage blocks
Per language, ordered list of blocks, each with `type`, `title`, `config_json`, `is_active`.

| Block type | Config |
|---|---|
| Lead story | 1 main + N secondary; manual picks, auto fill from category/latest |
| Top stories | Count, manual pins + auto fill |
| Category section | Category, count, layout variant (list/grid/featured+list) |
| Latest news | Count, optional category filter |
| Trending / Most read | Period (today / 7 days), count |
| Gallery section | Gallery or latest N galleries |
| Video section | Latest articles that contain a video embed, count |
| Ad slot | Slot key |
| Custom HTML | Sanitized, requires `blocks.raw_html` |

Manual picks always win; remaining places auto-fill. Unpublished/deleted articles in manual picks are skipped silently. Changes purge homepage cache immediately.

---

## 7. Media library

### 7.1 Features
- Upload via drag-and-drop and file picker, multi-file, with progress and per-file errors.
- Folders (nested), search by name/title/alt, filter by type/date/uploader, grid and list views.
- Metadata: `title`, `alt`, `caption`, `credit`, focal point (click on image). Block-level overrides take precedence in articles.
- Usage tracking: "Used in N items" with links; deleting used media shows a warning and requires confirmation; deletion of used media is blocked for non-Admin.
- Bulk actions: move, delete, download original.
- Storage usage meter (total size on the configured disk, per-type breakdown) on the media page and system page.

### 7.2 Allowed types and limits (all configurable by env)

| Type | Extensions | Default max |
|---|---|---|
| Image | jpg, jpeg, png, webp, gif, avif | 20 MB |
| Document | pdf | 25 MB |
| Audio | mp3, m4a | 50 MB |
| Video | mp4, webm | 200 MB (no transcoding; recommend YouTube for large video) |
| Blocked | svg, html, php, executables, archives | Always rejected |

MIME type is verified from file contents, not the extension. Stored filenames are randomized (`{yyyy}/{mm}/{uuid}.{ext}`); original name kept in the DB.

### 7.3 Image pipeline (queued, never in the request)
1. On upload: store original (EXIF stripped except orientation applied; if longest side > 4000 px, downscale original to 4000 px), compute SHA-256, dedupe (if the hash exists, reuse the existing media record and tell the uploader).
2. Queue `media` jobs create conversions:
   - Responsive widths: 320, 640, 1024, 1600 in WebP (and AVIF if supported), quality 80.
   - Named crops (using the focal point): `thumb` 1:1 300 px, `card` 16:9 640 px, `hero` 16:9 1600 px, `og` 1200×630 JPEG.
3. Until conversions finish, the original is served; the picker shows a "processing" badge.
4. Failed jobs retry 3 times, then appear on the system page with a "retry" action.
5. Theme component `<x-picture :media="$m" crop="card" sizes="…">` outputs `<picture>` with `srcset`, explicit `width`/`height`, and `loading="lazy"` (except LCP image: `fetchpriority="high"`, no lazy).

### 7.4 Storage
- `MEDIA_DISK` = `local`, `s3` (any S3-compatible incl. R2) via env. Code uses Laravel `Storage` only.
- `MEDIA_CDN_URL` optional base URL for public media.
- Capacity planning rule: budget 100 GB per 6 months per site (originals + conversions), plus DB and backups.
- Weekly job reports orphaned files (on disk, not in DB) and DB records with missing files. Cleanup is a manual command, never automatic.

### 7.5 Acceptance
- Uploading a 12 MB JPEG returns control to the user in under 3 s (excluding network transfer) and conversions complete in the background.
- Uploading the same file twice does not create a second stored file.
- An SVG or a PHP file renamed to `.jpg` is rejected.

---

## 8. Languages and URLs

### 8.1 Language configuration
- `APP_LOCALE` is the default language; `SITE_LANGUAGES` is the enabled list (e.g. `hi,en`). Default: one language.
- Default language URLs have no prefix; other languages use `/{lang}/…` (e.g. `/en/`).
- Every public page has `<html lang>`; RTL languages (e.g. `ur`) set `dir="rtl"`. Themes must use CSS logical properties.
- When more than one language is enabled: language switcher component, `hreflang` between linked translations, language tag in the news sitemap, per-language homepage blocks and menus.
- Article/page editors show "Create translation" which copies the structure into a new language version with a shared `translation_group_id`.
- Admin UI strings: all through `__()`; English admin shipped in v1; additional admin languages are a later add.

### 8.2 URL structure and resolution order

| Pattern | Resolves to |
|---|---|
| `/` | Homepage |
| `/{category}/{article-slug}-{id}` | Article (lookup by id; 301 to canonical if category/slug differ) |
| `/{category}` , `/{parent}/{child}` | Category listing |
| `/tag/{slug}` | Tag archive |
| `/author/{slug}` | Author page |
| `/gallery/{slug}` | Gallery page |
| `/search?q=` | Search results (`noindex`) |
| `/{slug}` | Static page |
| `/sitemap.xml`, `/news-sitemap.xml`, `/feed.xml`, `/feed/{category}.xml`, `/robots.txt`, `/ads.txt`, `/app-ads.txt` | Generated files |
| `/preview/{signed-token}` | Draft preview |
| `/t/v`, `/live/{id}/entries`, `/poll/{id}/vote` | Public machine endpoints |
| `/{ADMIN_PATH}/…` | Admin (default `admin`) |

Resolution order for a single-segment path: reserved routes → category → page → 404 (checked against redirects first).
**Reserved slugs** (cannot be used for categories/pages): `admin`, `search`, `tag`, `author`, `gallery`, `feed`, `sitemap`, `preview`, `live`, `poll`, `t`, `api`, `storage`, `assets`, `build`, plus the current `ADMIN_PATH`.

### 8.3 Slug rules
- Auto-generate from title: transliterate to Latin (e.g. Devanagari → readable Latin), lowercase, hyphens, strip punctuation, max 80 chars at word boundary.
- Editors may edit manually. Per-install option `UNICODE_SLUGS=true` keeps native script instead.
- Unique per language; on conflict append `-2`, `-3`.
- Once an article is published, editing the slug is allowed but shows a notice; the id-based lookup keeps the old URL working (301).

### 8.4 Typography and search notes
- Default theme self-hosts subsetted fonts for the enabled scripts per `docs/design.md` (Plus Jakarta Sans for Latin, Noto Sans Devanagari for Devanagari), `font-display: swap`, tuned line-height for Indic scripts.
- Public search v1 uses MySQL fulltext. **Phase 2 task:** validate tokenization on real Devanagari text; if inadequate, enable Meilisearch earlier.
- Legacy-font (e.g. Kruti Dev) pasted text is **not** converted in v1; the editor shows a warning if pasted text looks like legacy-font gibberish. A converter is a later add-on.

---

## 9. News features

### 9.1 Breaking news ticker
- Shows up to 5 latest published articles where `is_breaking = 1` and (`breaking_until` is null or in the future).
- Setting the flag defaults `breaking_until` to now + `BREAKING_DEFAULT_HOURS` (6). Expired items drop off automatically.
- Component cached 30 s and purged on any breaking change.

**Expiry contract:** `breaking_until` is evaluated on every read and by a scheduler/expiry job. Expired breaking articles MUST disappear from the ticker without requiring an editor action. A breaking-state change purges the ticker fragment immediately; the public ticker uses a maximum 30-second cache.

### 9.2 Trending / most read
- Data from analytics (§11). Two lists: **Trending** (time-decayed score) and **Most read** (today / 7 days).
- Per language. Cached 1–5 min. Excludes unpublished articles automatically.

### 9.3 Related articles
- Auto score: +3 per shared tag, +2 same primary category, minus age decay; same language; exclude self; default 4 results; cache 15 min.
- Manual related list on the article overrides auto results.

**Related-article scoring contract:** default related items are calculated in this order: shared tags contribute up to +3, same primary category contributes +2, and publication age applies a decay factor. Results must be same-language, exclude the current article, be published/indexable, and return the default configured count (4). Editors may override the automatic list manually.

### 9.4 Embeds (video and social)
Decision (D10): embeds use each provider's **official embed markup and script**, loaded **only on click**. No hand-rolled iframes, and no re-rendering of third-party post content in our own cards.

- **Providers (v1):** X, Instagram (posts, reels), Threads, Facebook (posts, videos, reels), TikTok, YouTube (incl. Shorts). Others are rejected with a message. New providers are added as adapters, including by modules (`Cms::embeds()->register()`).
- **Input:** editors paste either a post URL or the provider's embed code.
- **Storage (safety rule):** on save, the input is parsed into `provider` + canonical permalink (+ safe provider-specific fields, e.g. Instagram "captioned" flag). Pasted HTML and `<script>` are discarded and never stored or output. The server regenerates the provider's official markup from the stored fields using one template per adapter.
- **Page render:** a placeholder is output (provider icon, "Load {Provider} post" button, link to the original post, reserved min-height per provider to limit layout shift). No third-party code loads on page view.
- **On click:** the loader inserts the official markup, loads that provider's script once per page (X `widgets.js`, Instagram `embed.js`, Threads `embed.js`, Facebook SDK, TikTok `embed.js`), then runs the provider's re-scan routine for that element (adapter-specific). The placeholder is replaced by the rendered post.
- **YouTube exception:** YouTube has no blockquote-plus-script embed; its official embed is the player iframe. It follows the same rule: thumbnail with play button, and the official player (`youtube-nocookie.com`) loads on click.
- **Failure:** if the script is blocked (ad blocker, network) or the post is deleted, the placeholder stays with an "Open on {Provider}" link.
- **Load mode:** `EMBED_LOAD_MODE=click` (default) or `lazy` (loads when the embed nears the viewport).
- **Admin editor:** shows the same placeholder with a provider label and a "Load preview" button; no third-party code loads in admin unless clicked.
- **Allowlist:** script and iframe hosts per adapter live in config; the sanitizer and the raw HTML block use the same list. If the site administrator enables a CSP, these hosts must be allowed.
- **Maintenance risk:** exact markup and script details are verified against each provider's current official docs when its adapter is built, and each adapter has a smoke test. Platforms change these without notice.
- **Acceptance:** a page with 3 embeds makes no requests to provider script or iframe hosts before any click (YouTube thumbnails excepted); clicking one loads only that provider's script and renders the post; a blocked script or deleted post falls back to the link.

### 9.5 Live blog
- An article with `type = live`, `live_status` = `live` or `ended`.
- Entries (`live_blog_entries`): `title` (optional), `body_json/html`, optional media, `posted_at` (editable), `is_pinned`, `status` (`draft`/`published`), `author_id`.
- Admin: quick-composer at the top of the live blog editor; edit/delete entries; pin/unpin; end live blog.
- Public: entries newest first; pinned entries on top; "LIVE" badge while `live`.
- Updates: the page polls `GET /live/{id}/entries?since={ts}` every `LIVE_BLOG_POLL_SECONDS` (default 20) while the tab is visible and status is live; the endpoint is cached 10 s and returns only new/changed entries. Polling stops when `ended`.
- JSON-LD `LiveBlogPosting`. When ended, the page becomes a normal static article page and the cache TTL returns to normal.

### 9.6 Galleries
- `galleries`: `title`, `slug`, `description`, `language`, `status`. Items: `media_id`, `caption`, `credit`, `sort` (drag reorder).
- Public page `/gallery/{slug}`; embeddable in articles via block; grid + keyboard-accessible lightbox with captions.

### 9.7 Polls
- `polls`: `question`, options (2–10), `status` (`open`/`closed`), `closes_at`, `show_results` (`after_vote` / `always` / `after_close`).
- One vote per visitor: cookie-less hashed visitor id (same method as §11) plus a localStorage flag as a UX hint. Rate-limited. The vote endpoint is CSRF-exempt but origin-checked and rate-limited (D12).
- Admin: results with counts and percentages, CSV export, close poll.
- Lowest priority feature; may be cut from v1 without affecting others.

---

## 10. Scripts and Ads manager

Goal: admins manage third-party scripts and ad code from the admin panel with no code changes. Only users with `scripts.manage` (Admin by default) can access it.

### 10.1 Script snippets
Admin screen: list (name, type, position, scope, active, updated), create/edit, duplicate, enable/disable toggle, drag-to-reorder priority.

| Field | Details |
|---|---|
| `name` | Required |
| `type` | `analytics`, `ads`, `pixel`, `verification` (meta tag), `custom` |
| `code` | Raw text, code-editor input. Pasted exactly as given by the provider. Not sanitized (by design) |
| `position` | `head`, `body_start`, `body_end` |
| scope | `all`, `home`, `articles`, `categories` (select categories), `pages`, `custom` (URL path patterns with `*` wildcard); plus an exclude list |
| `priority` | Integer, lower loads first |
| `is_active` | Toggle |
| `production_only` | Default on; snippet is not output unless `APP_ENV=production` |
| `consent_category` | `necessary` / `analytics` / `marketing`. Stored for a future consent banner; v1 does **not** gate loading |
| `notes` | Free text |

Rules:
- Admin pages never receive snippets.
- Output is assembled once per scope and cached; the cache is cleared on any change.
- Preview/draft URLs do not receive analytics/ads/pixel snippets.
- Snippets are static HTML in the page, so the full-page cache stays valid.

### 10.2 Ad slots

| Field | Details |
|---|---|
| `key` | Unique slug, e.g. `header-leaderboard`, `sidebar-top`, `in-article-1`, `article-end` |
| `label`, `description` | For admins |
| `desktop_code`, `mobile_code` | Ad code per breakpoint; either may be empty (slot then hidden on that device) |
| `min_height_desktop`, `min_height_mobile` | Reserved px height to prevent layout shift |
| `lazy_load` | Load when slot nears the viewport (IntersectionObserver) |
| `is_active` | Toggle |
| `auto_insert` | For in-article slots: `enabled`, `after_paragraph` (N), `repeat_every` (optional), `max_per_article`, `min_paragraphs_required` |

Theme usage: `<x-ad-slot key="sidebar-top" />`. Article rendering also inserts slots with `auto_insert` rules, counting only top-level paragraph blocks.

**Rendering requirement (D9, important):** the server outputs one placeholder `div` carrying both codes encoded (e.g. base64 in data attributes). A small loader chooses the variant via `matchMedia`, then injects **only** that variant. Because `innerHTML` does not execute scripts, the loader must re-create each `<script>` element so ad code runs. Both variants must never be present as live markup at the same time (would double-count impressions).

### 10.3 Text files
Editors with save/reset-to-default for: `ads.txt`, `app-ads.txt`, `robots.txt`. Served dynamically as `text/plain`, cached. Default `robots.txt` disallows the admin path, search results, and previews, and lists the sitemap URLs.

### 10.4 Safeguards
- Every save stores a revision in `config_revisions` (who, when, full before/after). One-click rollback.
- All changes written to the audit log (§16).
- **Safe mode:** a logged-in admin visiting any public URL with `?no-scripts=1` sees the page with all custom snippets and ad slots disabled, to debug a broken site.
- A short warning above the code field explains that scripts run on every visitor's browser.
- Documented limitation: a strict Content-Security-Policy is not enabled by default because pasted inline scripts conflict with it.
- Performance defaults: documentation recommends `async`/`defer`; the system warns when a snippet adds more than N script tags (informational only).

### 10.5 Acceptance
- Pasting a GA4 snippet in `head` / scope `all` makes it appear in the HTML of public pages and not in admin pages, immediately after save.
- Toggling a snippet off removes it immediately (cache cleared).
- On a mobile viewport only `mobile_code` runs; on desktop only `desktop_code` runs; never both.
- Rolling back to a previous revision restores the exact previous code.

---

## 11. Analytics (first-party)

### 11.1 Purpose
Editorial insight and trending. Not a replacement for GA4 (installed through §10). Numbers will differ from GA4; this is documented in the admin UI.

### 11.2 Collection
- Article pages include a small JS beacon (sent after the page is visible for ≥ 2 s, using `navigator.sendBeacon` to `POST /t/v` with `article_id`, referrer host, and language).
- The endpoint boots minimal framework code, validates, and writes to Redis only; it returns `204`. It is CSRF-exempt, rate-limited, origin-checked, and sets no cookies.
- **Visitor hash** = `sha256(daily_salt + ip + user_agent)`; `daily_salt` rotates at local midnight (`APP_TIMEZONE`, default `Asia/Kolkata`). No raw IPs or events are stored.
- **Dedupe:** one view per `(article, visitor_hash)` per `ANALYTICS_DEDUP_MINUTES` (default 30) using Redis `SET NX EX`.
- **Bots:** user-agent denylist; requests without JS never reach the endpoint.
- Not counted: preview URLs, logged-in admin/editor sessions (flag in beacon), draft/unpublished articles.

### 11.3 Redis keys (per article)
- Daily views counter; hourly views counters (TTL 48 h); daily uniques HyperLogLog; daily referrer-bucket and device-bucket counters.
- Referrer buckets: `direct`, `search`, `social`, `other` (classified server-side from host lists). Device buckets: `mobile`, `desktop` (from viewport width sent by the beacon).

### 11.4 Persistence
- A scheduled aggregation job runs every minute to move counters into `article_stats_daily` (`article_id`, `date`, `views`, `uniques`, referrer and device columns) and update `articles.total_views`.
- Daily aggregates kept indefinitely. No raw event storage.

### 11.5 Trending score
Every 5 minutes: `score = Σ over last 24 hourly counters of views × 0.5^(age_hours / H)` where `H = TRENDING_HALF_LIFE_HOURS` (default 6). Top 50 per language stored in a Redis sorted set.

### 11.6 Admin analytics
- Dashboard widgets: views today / 7 d / 30 d with trend line; top 10 articles today; views by hour today.
- Analytics page: period selector (today, 7 d, 30 d, custom), top articles, top categories, top authors, referrer and device split, CSV export.
- Article editor sidebar: views today, last 7 days, total, top referrer bucket.
- Reporters see only their own articles' numbers (`analytics.view` scoped).

### 11.7 Acceptance
- With full-page cache on, viewing a cached article from a real browser increments its counter; viewing it twice within 30 min counts once.
- A request with a bot user-agent does not increment.
- Counters survive a Redis restart only to the extent of the last flush (documented; acceptable loss ≤ 1 minute).

---

## 12. Module system (extensibility)

### 12.1 Definition and v1 boundary

A module is a **first-party, optional feature** that extends NewsCMS without creating unnecessary coupling in the application. Modules use Laravel service providers, routes, migrations, views, Livewire components, events, policies, and the documented NewsCMS extension points. Optional modules should live in the application unless a demonstrated technical reason justifies a separate package.

The module system is not a customer-installable marketplace. No code upload or package installation is permitted from the admin UI. The supported module inventory is controlled in source control and deployment configuration.

### 12.2 What a module may contribute

A module may provide routes, migrations and tables it owns, permissions, settings, admin navigation, Livewire components, editor block types, homepage widgets, event listeners, scheduled commands, and theme components/views. Contributions must be explicit and documented; modules must not silently alter core tables or replace core security rules.

### 12.3 Stable extension surface

Only explicitly documented interfaces, events, Blade component contracts, theme slots, configuration keys, and module metadata are considered public extension APIs. Prefer Laravel events for side effects and ordinary interfaces for synchronous behaviour. Avoid a universal hook/filter system or central registry unless a concrete extension requirement needs one.

Public API changes follow the versioning policy in §24.4. Internal classes remain private and may change between releases.

### 12.4 Enablement and lifecycle

Module code is installed through Composer/deployment, never uploaded through the admin. Enabled modules are declared through reviewed configuration and/or the `modules` table where per-install toggling is required by the product. Enabling a module runs its documented migrations and permission synchronization; disabling it stops feature routes/UI from being exposed but does not delete its data or roll back migrations. Destructive data removal requires a separate explicit maintenance command and backup.

A module incompatible with the installed Core version must fail closed with a clear log and admin status message. Avoid dynamic dependency graph discovery and runtime topological sorting; use explicit declared dependencies and ordinary Laravel provider registration.

### 12.5 Rules

- First-party modules only (D11); no upload-and-install from the admin.
- Modules own their tables and migrations; they do not alter core tables without an approved schema change.
- Base CMS functionality must continue to work when an optional module is disabled.
- Built-in candidates: LiveBlog, Galleries, Polls. Final v1 inclusion remains subject to §22.
- Architecture tests enforce declared dependency boundaries without imposing one Composer package per capability.

### 12.6 Acceptance

At least one optional first-party module is covered by tests proving that its routes, permissions, UI, and scheduled work are absent or inactive when disabled; its data is preserved; and core editorial workflows continue to work. No custom framework generator is required for this acceptance test.

---

## 13. SEO, feeds, redirects

### 13.1 On-page SEO
- Title template setting (default `%title% | %site%`), meta description fallback chain (meta → summary → first 160 chars of body), canonical (self unless overridden), Open Graph and Twitter Card tags using the `og` crop, `robots` meta per `allow_indexing`.
- JSON-LD: `NewsArticle` (headline, image, datePublished, dateModified, author, publisher), `BreadcrumbList`, `Organization`, `WebSite` with `SearchAction`, `LiveBlogPosting` where relevant.
- Pagination uses clean `?page=N`; pages ≥ 2 get self-canonical.

### 13.2 Sitemaps and feeds
- `/sitemap.xml` index → child sitemaps for articles (chunks of 1000, with `lastmod`), pages, categories, tags, authors; unpublished and noindex items excluded.
- `/news-sitemap.xml`: articles published in the last 48 hours (max 1000), with publication name, language, title, publication date.
- RSS 2.0: `/feed.xml` (latest 30) and `/feed/{category}.xml`.
- All generated files are cached and purged on publish/unpublish.

### 13.3 Redirects
- Fields: `from_path` (exact, unique), `to_url`, `status_code` (301, 302, 410), `is_wildcard` (prefix match, `*` suffix), `hits`, `last_hit_at`.
- CSV import (from,to,code) with validation report; CSV export.
- 404 log: aggregated paths with hit count, last seen, last referrer; one-click "create redirect" from a 404 row. Kept 90 days.
- Redirect loops are detected and rejected on save.
- Redirects are checked before the normal route resolution.

---

## 14. Search
- Public: `/search?q=` over article title, summary, `body_text`, in the current language; paginated (10/page); `noindex`; results sorted by relevance then date; query length min 2, max 100; rate-limited.
- Admin: article list search (title, slug, id, author).
- Driver switch `SEARCH_DRIVER=mysql|meilisearch`; the Meilisearch driver uses Laravel Scout with per-language indexes. Operators must have a documented way to rebuild the index.

---

## 15. Admin panel requirements

### 15.1 Navigation
Dashboard · Articles (All, Create, Pending review, Scheduled, Trash) · Categories · Tags · Authors · Galleries · Polls · Media · Pages · Menus · Homepage · Redirects (with 404 log) · Scripts & Ads (Snippets, Ad slots, Text files) · Analytics · Users & Roles · Settings · Modules · Audit log · System status · My profile.
Items are shown only if the user has the permission. Modules may add items.

### 15.2 Dashboard widgets
Pending review count (Editors), upcoming scheduled articles, today's views, top articles today, storage usage, system warnings (Admin: queue not running, scheduler stale, failed jobs, disk > 80%).

### 15.3 Admin component foundation (build early)

Build a small, reusable newsroom component layer using **Livewire + Blade + Flux UI**. Flux supplies UI primitives; NewsCMS still owns the behaviour, accessibility, data loading, permissions, and workflow logic.

Required reusable components/patterns:

- Resource index with search, filters, sorting, pagination, bulk actions, and per-user page-size preference.
- Resource form patterns with validation, save/flash states, dirty-state warnings, and clear error recovery.
- Media picker and upload progress/status.
- Structured rich editor for blocks, including schema validation and accessible controls.
- Slug field, status badge, confirmation dialog, language tabs, timezone-aware date/time picker, notifications/toasts, and safe code editor for the scripts manager.
- Consistent loading, empty, error, disabled, permission-denied, and success states.

A simple CRUD resource should reuse these patterns rather than implementing its own table/form behaviour. Do not build a generic admin framework or duplicate functionality already supplied by Laravel, Livewire, or Flux. Pin compatible versions after checking the selected Laravel, Livewire, Tailwind, and Flux release lines at implementation kickoff.

---

### 15.4 Notifications
- In-admin notification bell (database notifications): article submitted for review, approved/rejected with note, scheduled article published, failed media jobs (Admin).
- Email for the same events is optional per user (setting), off by default.

### 15.5 Users and authentication
- Invite by email (signed link, 48 h) or create with temporary password; set role; deactivate (never hard-delete users who own content).
- Password min 10 characters; login throttle 5 attempts/min per email+IP; password reset by email.
- Optional TOTP 2FA per user; setting `FORCE_ADMIN_2FA` makes it mandatory for Admin role.
- "Log out other sessions" on profile. Admin session idle timeout configurable (default 120 min).
- Admin path configurable via `ADMIN_PATH`.

### 15.6 Settings screens (stored in `settings`, per language where noted)

The shared settings UI MUST expose, at minimum, the following product controls: `TAG_INDEX_MIN_ARTICLES`, `require_featured_image`, `require_alt_text`, default pagination counts, media trash retention, revision retention, SEO title template, SEO/meta fallbacks, default OG image, robots defaults, breaking default hours, analytics/trending parameters, language/locale settings, and cache/purge defaults. Settings are grouped by the capability that owns them and exposed through shared settings UI.

- **General:** site name (per language), tagline (per language), logo light/dark, favicon, default language, timezone display.
- **Contact:** email, phone, address (per language).
- **Social:** profile URLs.
- **SEO defaults:** title template, default OG image, default `robots` behaviour, `TAG_INDEX_MIN_ARTICLES`.
- **Content:** `require_featured_image`, `require_alt_text`, articles per page (default 20), trash retention days (default 30), revision count (default 50).
- **Appearance:** active theme, validated theme options (defined by `theme.json`).
- Env-only (not editable in admin): DB, Redis, disk credentials, mail transport, `APP_KEY`, `ADMIN_PATH`, limits.

---

## 16. Audit log
- Records: login success/failure, logout, user/role changes, setting changes, script/ad/text-file changes, module enable/disable, article publish/unpublish/delete/restore, redirect changes, media deletions.
- Fields: `user_id`, `action`, `subject_type`, `subject_id`, `changes_json` (before/after for tracked fields; secrets masked), `ip`, `user_agent`, `created_at`.
- Admin-only viewer with filters (user, action, date, subject) and CSV export. Immutable from the UI. Retention `AUDIT_RETENTION_DAYS` (default 365), pruned daily.

---

## 17. Theming (public site)

- Public pages use server-rendered Laravel Blade with Tailwind CSS and minimal JavaScript. No SPA and no separate template engine.
- NewsCMS owns a **small, purpose-built theme system built on Laravel's view finder, Blade components, and Vite**. It controls presentation only; it does not own editorial rules, permissions, queries, or business logic.
- A theme is a folder containing `theme.json`, `views/`, `assets/`, and optional language resources. The metadata describes name/version, supported languages, typed appearance options, and required view mappings.
- Theme management must support an active theme per install, validated theme settings, deterministic template resolution, asset URL resolution, reusable layouts/components, named content/ad slots, homepage composition using approved widgets, and safe fallback to the shipped default theme when a view is absent.
- Required templates: home, article, live article (when LiveBlog is enabled), category, tag, author, search, page, gallery (when Galleries is enabled), 404, 410, and error.
- Core supplies stable presentation components including `x-picture`, `x-ad-slot`, `x-breaking-ticker`, `x-trending`, `x-related`, `x-pagination`, `x-lang-switcher`, `x-share`, and `x-embed`.
- Themes receive prepared view data from controllers/query services or typed view models; they must not run ad-hoc database queries or enforce publishing permissions.
- Missing theme views fall back to the default theme. Invalid theme configuration must produce a clear validation error and safe default, not a blank public page.
- Core ships one polished default news theme: responsive, accessible, Indic-ready, RTL-aware where configured, and with optional dark mode. Theme assets are built through Vite in CI/release tooling, not on production servers.
- Child themes, a theme marketplace, a drag-and-drop page builder, and arbitrary PHP extensions are out of scope for v1 unless separately approved.

---

## 18. Performance and caching

### 18.1 Targets (initial; validate in Phase 5)
- Cached article page TTFB at CDN edge < 200 ms; uncached origin render < 500 ms on the reference server.
- Core Web Vitals "good" on default theme (see §1.5).
- Admin list pages p95 < 1.5 s with 10k articles.

### 18.2 Full-page caching (D12)
- Public GET pages are cached by `URL + language`. Never cached: admin, preview, search with query, requests with an admin session cookie.
- Public pages **must not set cookies** and must not contain CSRF tokens.
- Headers: `Cache-Control: public, s-maxage=…, stale-while-revalidate=…`. TTL defaults: article 10 min, listings 2 min, homepage 60 s (purge-driven; TTLs are the fallback).
- On publish/update/unpublish/delete/breaking change: purge the article URL, its category pages, homepage, tag and author pages, feeds, sitemaps, ticker/trending fragments.
- Pluggable `CdnPurger` interface: `null` and `cloudflare` drivers in v1.

### 18.3 Application
- Redis for cache, sessions (admin only), queues. `Model::preventLazyLoading()` outside production. Required DB indexes listed in §20.
- Queues: `default`, `media`, `stats`. Scheduler jobs: publish scheduled (1 min), flush stats (1 min), expire breaking (5 min), trending (5 min), prune audit/404/trash (daily), orphan media report (weekly), backup (nightly).

---

## 19. Security and privacy
- All admin routes require auth + permission policy; CSRF on all admin forms; rate limits on login, upload, public POST endpoints.
- Output escaping by default; rich content sanitized with an allowlist on save; raw HTML limited and permission-gated.
- Uploads: content-type sniffing, random names, no execution on upload paths, SVG blocked.
- Security headers: HSTS, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `X-Frame-Options: SAMEORIGIN`, `Permissions-Policy` minimal. CSP optional per install, off by default.
- Secrets only in `.env`; backups encrypted at rest; `composer audit` and `npm audit` in CI fail on high severity.
- Privacy: analytics stores no personal data (daily-salted hashes, no raw IPs). The site owner remains responsible for consent/legal text; the consent-category field exists for a later banner.

---

## 20. Data model

**Media relationship rule:** article featured media, article OG media, category images, and author photos are Spatie Media Library attachments managed through the media capability. They are intentionally not represented as `*_media_id` foreign-key columns in the domain tables.


Primary keys are `bigint unsigned` unless noted. All tables have `created_at`/`updated_at` unless noted. `utf8mb4`.

```
users            id, name, email (unique), password, is_active, two_factor_secret,
                 two_factor_confirmed_at, last_login_at, locale, email_notifications bool
                 (roles via spatie/laravel-permission, ADR-0002; exactly-one-role enforced in app, §4.1)
roles, permissions, role_permission
                 (superseded by ADR-0002: package tables own role/permission records;
                 permission keys remain code-owned, §4)
authors          id, user_id FK null, name, slug (unique), bio,  email null,
                 social_json, is_active
articles         id, type enum(standard,live), language char(5), translation_group_id char(36) null,
                 title, subtitle, slug, summary, body_json longtext, body_html longtext,
                 featured_caption, featured_credit, primary_category_id FK,
                 author_id FK, dateline, source, status enum(draft,in_review,scheduled,published,unpublished),
                 published_at datetime null, is_breaking bool, breaking_until datetime null,
                 live_status enum(live,ended) null, allow_indexing bool default 1, meta_title, meta_description,
                 canonical_url,  reading_time_min smallint, total_views bigint default 0,
                 review_note text null, created_by FK, updated_by FK, deleted_at
                 INDEX (status, published_at), INDEX (language, status, published_at),
                 INDEX (primary_category_id, status, published_at), INDEX (is_breaking, breaking_until),
                 UNIQUE (language, slug), INDEX (translation_group_id)
article_authors  article_id, author_id, sort                      (co-authors)
article_related  article_id, related_article_id, sort
revisions          id, subject_type, subject_id, user_id, kind enum(manual,autosave), snapshot_json, created_at
terms             id, vocabulary enum(category,tag), parent_id null, sort, color, show_in_menu, is_active
term_translations id, term_id, language, name, slug, description, meta_title, meta_description
                 UNIQUE (language, slug)
termables        term_id, termable_type, termable_id

media_folders    id, parent_id null, name                        (NewsCMS-owned, ADR-0001)
media_folder_media folder_id FK, media_id, sort                (NewsCMS-owned link table, ADR-0001)
media, media_conversions
                 (superseded by ADR-0001: Spatie Media Library owns records/conversions)
media_usages     media_id, usable_type, usable_id           (kept in sync on save)
pages            id, language, translation_group_id null, title, slug, body_json, body_html, template,
                 status enum(draft,published), meta_title, meta_description, allow_indexing,
                 created_by, updated_by, deleted_at         UNIQUE (language, slug)
menus            id, name, location enum(header,footer,mobile), language
menu_items       id, menu_id, parent_id null, label, type enum(category,page,article,tag,url),
                 target_id null, url null, open_new_tab, sort, is_active
layout_blocks    id, region, language, widget, title, config_json, sort, is_active
settings         (superseded by ADR-0004: spatie/laravel-settings typed store;
                 language-scoped keys + validation defined there)
redirects        id, from_path (unique), to_url, status_code smallint, is_wildcard bool, hits, last_hit_at, created_by
not_found_log    id, path (unique), hits, last_seen_at, last_referrer
script_snippets  id, name, type, code mediumtext, position, scope_type, scope_json, exclude_json,
                 priority int default 100, is_active, production_only default 1, consent_category,
                 notes, created_by, updated_by
ad_slots         id, key (unique), label, description, desktop_code mediumtext, mobile_code mediumtext,
                 min_height_desktop int, min_height_mobile int, lazy_load, is_active, auto_insert_json
site_files       id, name enum(ads.txt,app-ads.txt,robots.txt), content text    (or in settings)

audit_logs       (superseded by ADR-0003: spatie/laravel-activitylog authoritative store;
                 retention AUDIT_RETENTION_DAYS)
modules          id, name (unique), version, enabled
article_stats_daily article_id, date, views int, uniques int, ref_direct, ref_search, ref_social,
                 ref_other, dev_mobile, dev_desktop           PRIMARY (article_id, date)  INDEX (date, views)
galleries        id, language, title, slug (unique per language), description, status
gallery_items    id, gallery_id, media_id, caption, credit, sort
live_blog_entries id, article_id, title null, body_json, body_html, media_id null, posted_at, is_pinned,
                 status enum(draft,published), author_id
polls            id, language, question, status enum(open,closed), closes_at null, show_results enum
poll_options     id, poll_id, label, sort
poll_votes       id, poll_id, option_id, voter_hash char(64), created_at   UNIQUE (poll_id, voter_hash)
+ Laravel standard: notifications, jobs, failed_jobs, job_batches, cache, sessions
```

---

## 21. Operations and installation

### 21.1 Setup and maintenance requirements

The application must have documented, repeatable procedures for:

- Configuring a fresh development, staging, or production environment and creating the first administrator securely.
- Applying database migrations, seeding only appropriate defaults, rebuilding Laravel caches, and restarting queue workers during a release.
- Checking application readiness, database/cache connectivity, queue and scheduler health, storage capacity, pending migrations, and backup recency.
- Synchronizing newly introduced permissions without overwriting administrator-configured role assignments.
- Reindexing search, validating themes, and reporting missing/orphaned media where those features are implemented.
- Testing database backup restoration and documenting media backup/restore procedures.

Use Laravel's existing setup and maintenance commands wherever they are sufficient. A NewsCMS-specific command is justified only when it combines a real application-specific workflow or validation that would otherwise be error-prone. The exact command names and implementation belong in `TRD.md`, not in this product-requirements document. Do not require a custom installer, upgrade framework, generator suite, or deployment/fleet orchestrator merely to run a normal Laravel application.

### 21.2 Environment variables (minimum set)

| Variable | Meaning |
|---|---|
| `APP_NAME`, `APP_ENV`, `APP_DEBUG`, `APP_URL`, `APP_KEY` | Standard |
| `APP_TIMEZONE` | Default `UTC` — all storage and runtime in UTC (corrected per TRD A2; was `Asia/Kolkata`) |
| `SITE_TIMEZONE` | Display-only timezone, default `Asia/Kolkata` (TRD A2) |
| `APP_LOCALE`, `SITE_LANGUAGES` | Default language and enabled list |
| `DB_*`, `REDIS_*`, `CACHE_STORE`, `QUEUE_CONNECTION`, `SESSION_DRIVER` | Infrastructure |
| `MEDIA_DISK`, `MEDIA_CDN_URL`, `AWS_*` (key, secret, region, bucket, endpoint, path-style) | Media storage |
| `MAX_UPLOAD_IMAGE_MB`, `MAX_UPLOAD_DOC_MB`, `MAX_UPLOAD_AUDIO_MB`, `MAX_UPLOAD_VIDEO_MB` | Limits |
| `MAIL_*` | SMTP |
| `ADMIN_PATH`, `FORCE_ADMIN_2FA`, `ADMIN_IDLE_TIMEOUT_MIN` | Admin access |
| `CDN_PURGE_DRIVER`, `CLOUDFLARE_ZONE_ID`, `CLOUDFLARE_API_TOKEN` | Cache purging |
| `SEARCH_DRIVER`, `MEILISEARCH_HOST`, `MEILISEARCH_KEY` | Search |
| `ANALYTICS_ENABLED`, `ANALYTICS_DEDUP_MINUTES`, `TRENDING_HALF_LIFE_HOURS` | Analytics |
| `BREAKING_DEFAULT_HOURS`, `LIVE_BLOG_POLL_SECONDS`, `TAG_INDEX_MIN_ARTICLES`, `UNICODE_SLUGS`, `EMBED_LOAD_MODE` | Content behaviour |
| `AUDIT_RETENTION_DAYS` | Audit retention |
| `SENTRY_DSN` | Error reporting |

### 21.3 System status page (`system.view`)
PHP/application versions, queue worker heartbeat, scheduler last run, failed jobs (retry/flush), Redis and DB connectivity, media disk write test, disk and DB size, last backup timestamp, pending migrations.

### 21.4 Deployment and backups
- Deploy to the chosen hosting environment using a documented repeatable release process. CI runs tests and builds assets; deployment runs reviewed migrations, checks `/up` and application status, and follows a documented rollback/forward-fix procedure.
- Backups: nightly DB dump plus media (object-storage versioning or snapshot), retention 14 daily + 8 weekly; restore test monthly; last-backup timestamp shown on system page.
- Monitoring: Sentry, external uptime check, alert on queue stall, disk > 80%, failed job spike.

---

## 22. Open items to confirm (blockers listed with the phase they block)

| # | Question | Blocks |
|---|---|---|
| Q1 | Block-based editor (TipTap) as D14 — CONFIRMED 2026-10-09 | Phase 2 |
| Q2 | Launch languages Hindi + English, no RTL at launch — CONFIRMED 2026-10-09 | Phases 1 and 3 |
| Q3 | Contributor scope — CONFIRMED 2026-10-09: Reporter minus category/tag creation (§4.1) | Phase 1 |
| Q4 | Confirm production hosting target and deployment method | Phase 5 |
| Q5 | Confirm the minimum supported production server and backup destination | Phase 5 |
| Q6 | Confirm design target (100k page views/day, 10x spikes) and reference server | Phase 5 |
| Q7 | Is video file upload needed in v1 or embeds only? | Phase 2 |
| Q8 | Confirm Polls stay in v1 (lowest priority, may be cut) | Phase 4 |

---

## 23. Delivery plan — product-first, architecture-light

Build a working Laravel application early. Establish only the reusable boundaries, tests, and operational foundations needed to safely deliver the product. Do not spend a phase building a custom runtime framework before the editorial workflow exists.

| Phase | Scope | Exit criteria |
|---|---|---|
| 0. Baseline and decisions | Inspect repository state; verify Laravel/PHP/DB; establish coding conventions, CI, architecture tests, package allowlist, and unresolved decisions | Reproducible local setup; baseline tests pass; architecture and package decisions documented; no unnecessary runtime framework added |
| 1. Foundation and admin shell | Authentication, roles/permissions, settings, audit log, revisions/concurrency, Livewire + Flux UI foundation, localization | Fresh install reaches a secure admin; role permissions and base admin states are tested |
| 2. Editorial core | Articles, author/bylines, categories, tags, pages, media, editor blocks, drafts/review/publish/schedule, revisions and preview | End-to-end editorial workflow passes, including conflict handling and media processing |
| 3. Public site and themes | Theme resolver, default theme, layouts/widgets/slots, URLs, search, SEO, sitemaps, RSS, redirects and 404 tooling | Public templates render correctly; SEO/feed/redirect tests pass; theme fallback works |
| 4. Newsroom features and growth | Breaking news, trending/related, embeds, scripts/ads, first-party analytics, notifications, selected optional modules | Feature acceptance passes without breaking public cache or security guarantees |
| 5. Operations and pilot | Repeatable setup/release/status procedures, backups/restore, deployment, observability, accessibility, performance and load tests | Restore drill and rollout test pass; pilot success criteria in §1.5 are met |

Phase estimates are intentionally omitted until repository audit and the first implementation plan establish the actual baseline. Split each phase into small executable tasks with tests and a phase-level audit; do not require approval for every internal task when the agreed phase scope is unchanged.

---

## 24. Quality requirements

### 24.1 Testing
- Architecture tests are mandatory for the boundaries that matter: forbidden dependency directions, circular dependencies, table ownership, authorization in public/admin query paths, and module isolation. Keep the rules understandable and tied to concrete failure modes.
- Required automated tests: permissions per role, article state machine and scheduling, slug generation (Latin and Devanagari), URL resolution and 301 behaviour, revisions and conflict detection, media dedupe and type rejection, redirects (incl. loop detection), script/slot rendering and scope rules, analytics dedupe/flush/trending, sitemap content, cache purge on publish.
- At least one browser test (Playwright/Dusk) per phase covering the main happy path.
- Larastan at level 6 minimum; Pint enforced in CI; no failing CI = no merge.

### 24.2 Accessibility and compatibility
- Default theme and admin target WCAG 2.1 AA for contrast, focus visibility, keyboard operation, form labels; images need alt text.
- Browsers: last 2 major versions of Chrome, Edge, Firefox, Safari; iOS Safari 15+; Android Chrome.

### 24.3 Definition of Done (every task)
1. Matches this PRD (or the PRD is updated in the same PR).
2. Permissions enforced and tested.
3. Validation messages and empty/error states handled.
4. Audit-log entries added where §16 requires.
5. Cache purge events fire where content changes public output.
6. Strings use `__()`; no hardcoded language-specific text.
7. Tests written and passing; static analysis and Pint clean.
8. Migrations are reversible where practical; destructive changes require a documented migration and recovery path.
9. Documented (code docs for the public API, admin help text for new screens).

### 24.4 Versioning policy
Use clear application release versions and document schema/configuration changes. Keep upgrades repeatable and provide an upgrade guide for breaking changes. Do not create a separate Core versioning or customer-fleet rollout policy unless the product scope changes to require distribution across multiple installations.

---

## 25. Risks

| Risk | Mitigation |
|---|---|
| Feature requests cause unnecessary coupling | Clear capability boundaries, theme overrides, and documented extension points |
| Risky application upgrades | Backups, tested migrations, release checklist, and rollback procedure |
| A bad pasted script breaks a live site | Revisions with rollback, audit log, safe mode |
| Image processing spikes on bulk uploads | Queues, rate limits, size limits |
| Indic text issues (legacy fonts, search quality, font weight) | Early validation in Phase 1, real newsroom content in pilot |
| Custom admin scope creep | PRD freeze for v1; base-component rule |
| CDN/cache complexity causing stale pages | Purge-on-event plus short TTL fallback; purge tests |
| Estimates optimistic | Phase exit criteria; re-estimate after Phase 0 |

---

## 26. Change control
Any change to scope, fields, statuses, URLs, or permissions is made by editing this file in the same pull request as the code, with a note in the Decision log if it reverses a decision. Unconfirmed assumptions in §22 must be resolved by the project owner before the dependent phase starts.