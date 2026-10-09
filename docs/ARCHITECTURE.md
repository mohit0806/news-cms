# ARCHITECTURE — NewsCMS

**Status:** Draft v1.0 — Laravel-native modular monolith  
**Updated:** 2026-10-09  
**Companion documents:** `PRD.md` (product behaviour), `TRD.md` (technical implementation)  
**Purpose:** Define the codebase structure, capability ownership, dependency rules, and boundaries for NewsCMS. This document does not replace Laravel's application architecture.

## 1. Architectural direction

NewsCMS is one ordinary Laravel application for one news publication/site at a time. It contains the staff admin portal, editorial workflows, content and media management, public website, theme system, and application configuration in one repository and deployment unit.

Laravel remains the framework and runtime. NewsCMS uses Laravel's service container, service providers, routing, middleware, Eloquent, migrations, policies, events/listeners, queues, scheduler, cache, Blade, Vite, and Artisan.

The application is a **modular monolith**: cohesive capabilities have clear responsibilities and ownership, but they are not automatically separate Composer packages, services, or independent runtimes. “Engine” may be used informally to describe a capability boundary only; it does not mean a custom engine framework.

### 1.1 Explicitly not part of this architecture

- A custom Kernel, dependency-injection container, event bus, command bus, hook framework, or runtime module manager.
- A package per capability, engine manifests, package registries, registry-freeze lifecycle, generated dependency graphs, or per-capability Testbench harnesses.
- Microservices or distributed service-to-service communication.
- A separate `newscms-core` package, customer starter application, multi-tenant platform, customer-installation fleet, or licensing/billing system.
- A custom template language or a third-party CMS theme engine.
- A universal repository/service/action class for every model or operation.

Use a Laravel feature directly unless a concrete product requirement or repeated problem justifies a small additional abstraction.

## 2. Relationship between the project documents

| Document | Owns | Must not become |
|---|---|---|
| `PRD.md` | User-visible behaviour, product scope, roles, workflows, acceptance criteria, product non-goals | A directory-layout or implementation manual |
| `TRD.md` | Stack, package candidates, implementation conventions, security/performance techniques, deployment and testing requirements | A competing architecture catalogue or product-spec replacement |
| `ARCHITECTURE.md` | Application shape, canonical directory layout, capability responsibilities, dependency direction, ownership boundaries, allowed integration patterns | A second framework specification or exhaustive class-by-class design |

If the documents disagree, do not silently pick one. Record the conflict and resolve it in the relevant source document or a short Architecture Decision Record (ADR). Product behaviour is defined by the PRD; the TRD constrains implementation; this document defines structural boundaries.

## 3. Canonical application layout

Keep the normal Laravel project skeleton. The tree below shows the intended responsibility areas, not a requirement to create every directory on day one. Add folders when their code exists.

```text
newscms/
├── app/
│   ├── Actions/                 # Meaningful use cases / transaction boundaries
│   ├── Console/                 # Focused Laravel Artisan commands, when needed
│   ├── Domain/
│   │   ├── Articles/            # Article rules, models, policies, queries, events
│   │   ├── Authors/
│   │   ├── Taxonomy/            # Categories and tags
│   │   ├── Pages/
│   │   ├── Navigation/          # Menus
│   │   ├── Homepage/            # Homepage composition and widgets
│   │   ├── Media/               # Media workflows around the chosen media package
│   │   ├── Publishing/          # Publishing and scheduling coordination
│   │   ├── Search/
│   │   ├── Seo/                 # Metadata, canonical URLs and structured output
│   │   ├── Redirects/
│   │   ├── Themes/              # Theme selection, validation and view resolution
│   │   ├── ScriptsAds/
│   │   ├── Analytics/
│   │   ├── Settings/
│   │   ├── Identity/            # Staff account/role integration
│   │   ├── Audit/
│   │   └── Shared/              # Only genuinely cross-cutting, non-domain utilities
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Middleware/
│   │   └── Requests/
│   ├── Livewire/Admin/          # Admin screens and UI orchestration
│   ├── Jobs/
│   ├── Listeners/
│   ├── Mail/
│   ├── Models/                  # Keep only if the project uses a shared model location;
│   │                             # avoid duplicate ownership with Domain/*/Models
│   ├── Notifications/
│   ├── Policies/
│   ├── Providers/
│   └── View/                    # View models/composers only where useful
├── bootstrap/
├── config/                      # Laravel config + focused newscms/media/search/etc. config
├── database/
│   ├── factories/
│   ├── migrations/              # Laravel's normal migration system
│   └── seeders/
├── public/
├── resources/
│   ├── views/                   # Shared admin, email, error and component views
│   ├── js/                      # Admin and public entry points
│   ├── css/
│   └── lang/                    # Or Laravel's selected lang/ convention; choose one consistently
├── routes/                      # web.php, console.php and focused files if justified
├── themes/
│   └── default/
│       ├── theme.json
│       ├── views/
│       ├── assets/
│       └── lang/                # Optional theme-specific strings
├── modules/                     # Optional first-party modules only, when implemented
│   ├── LiveBlog/
│   ├── Galleries/
│   └── Polls/
├── storage/
├── tests/
│   ├── Unit/
│   ├── Feature/
│   ├── Architecture/
│   └── Browser/
├── artisan
├── composer.json
├── package.json
└── vite.config.js
```

### 3.1 Layout rules

1. Keep Laravel's normal bootstrap, configuration, routing, migration, testing, and asset conventions.
2. Do not create a parallel `src/` application root. Application classes use the normal `App\\` namespace unless a demonstrated need changes that decision.
3. The sample `Domain/` folders are capability groupings, not mandatory DDD ceremony. Keep a small feature simple; don't create empty folders or boilerplate classes just to satisfy a diagram.
4. Avoid duplicate class ownership. Application models live in the shared `app/Models` location (decided P0-T03; first model: `App\Models\User`). Do not create a competing `app/Domain/{Capability}/Models` collection for the same entities.
5. `routes/web.php` and `routes/console.php` remain the defaults. Separate route files such as `admin.php`, `public.php`, or `machine.php` may be added only for clarity and must be registered through Laravel's normal routing mechanism.
6. All application migrations use `database/migrations/` and Laravel's migration system. A module may own migration files in its source directory only if the selected loading approach is explicit, tested, and still uses Laravel migrations; otherwise keep migrations in the standard directory.
7. The `themes/` and `modules/` directories are application-level conventions, not Composer package roots.

## 4. Capability map and ownership

The following areas describe responsibilities. They do not require separate packages, service providers, database connections, or runtime registration systems. A capability may be a namespace, a small group of classes, or a larger subsystem depending on its real complexity.

| Capability | Owns / coordinates | Examples of code |
|---|---|---|
| Articles & editorial | Article lifecycle, validation rules, revisions/conflict checks, bylines association, article-specific policy | Models, policies, actions, queries, publishing events |
| Taxonomy | Category/tag records and translations, hierarchy, term assignment rules | Taxonomy models, queries, slug rules |
| Authors | Public byline/profile data and links to staff accounts where applicable | Author model/policy/query |
| Pages | Static pages, translations, publication state | Page actions, policies, queries |
| Navigation & homepage | Menus, menu items, homepage sections/widgets and ordering | Prepared view data, widget definitions |
| Media | Upload workflow, processing state, metadata, usage tracking and Media Library integration | Upload actions, jobs, validation, media policies |
| Editor & rendering | Validated editor document schema and safe server-side HTML rendering | Block schema, renderer, block view partials |
| Publishing | Scheduling and coordinated publish/unpublish transitions, where not owned directly by Articles | Scheduled jobs, events, transition actions |
| Public routing | Resolve article/category/tag/author/page URLs and canonical redirects | Route handlers, path resolver, URL services |
| Search | Public search query contract and selected search driver | Search service/interface, MySQL or Scout driver |
| SEO & discovery | Metadata, canonical URLs, robots directives, sitemaps, RSS/Atom feeds, crawler files | Metadata builders, feed/sitemap generators |
| Themes & presentation | Active theme, validated options, Blade view resolution, assets, layout regions and slots | Theme resolver, validators, view models/components |
| Scripts & ads | Approved managed snippets, ad slots, targeting configuration, safe-mode behaviour | Policies, renderer, audit integration |
| Analytics | First-party beacon handling, aggregation, trending and admin reports | Endpoint, rate limits, jobs, queries |
| Identity & access | Staff authentication integration, roles, permissions, user activation and account rules | Policies/Gates, middleware, package integration |
| Settings | Typed site settings, validation, defaults and cache invalidation | Settings definitions and admin screens |
| Audit | Security- and product-relevant audit records and admin review | Activity/event integration, retention job |
| Operations & health | Readiness/status display and documented operational workflows | Health checks, focused commands, status queries |
| Notifications | In-admin/email notifications for defined editorial events | Laravel notifications and listeners |
| Optional modules | Feature-specific capabilities such as LiveBlog, Galleries, Polls | Module-specific models, policies, routes, views, tests |

This map is a responsibility guide, not a demand to implement every row as an independent service. Combine closely related responsibilities when doing so makes the code clearer and preserves ownership.

## 5. Dependency and collaboration rules

### 5.1 Allowed dependency direction

Use this practical direction of dependencies:

```text
Routes / Controllers / Livewire UI
              ↓
Application Actions / Use Cases (when useful)
              ↓
Domain models, policies, queries and capability services
              ↓
Laravel infrastructure: Eloquent, cache, events, queues, notifications, storage
```

This is guidance rather than a mandatory class-per-layer pipeline. Some Laravel mechanisms (policies, events, jobs, notifications, and view models) are called by the framework at their normal extension points.

### 5.2 Rules

1. **Presentation does not own business rules.** Controllers and Livewire components orchestrate requests, validate inputs, authorize operations, and return responses. Complex or reusable use cases belong in an action/service with a clear responsibility.
2. **Views do not query the database.** Blade views, theme templates, and UI components receive prepared data. Do not put publishing decisions, permission checks, or domain queries in templates.
3. **Capability ownership is explicit.** The capability responsible for an entity/table owns its write rules and migrations. Other capabilities should call a public method/query/action or react to an event instead of writing directly to the owner's tables.
4. **Avoid circular dependencies.** If Articles calls Themes and Themes calls Articles, move the shared orchestration to a suitable application-level service or pass prepared data across the boundary.
5. **Use Laravel events for real side effects.** Events/listeners are appropriate when independent work must react to a business transition—for example, publish invalidates caches, updates search, and sends notifications. Do not add an event for every method call.
6. **Use interfaces only when they earn their cost.** Good reasons include an alternative implementation, a stable extension point, or a useful test seam. Do not create an interface for every class.
7. **Keep event payloads small.** Prefer identifiers and explicit values for queued/event payloads; queued handlers must be idempotent when retries are possible.
8. **No direct foreign-table writes.** A capability may read another capability's public query/API, but it must not silently modify that capability's records or schema.
9. **No custom global registries by default.** Laravel configuration, service providers, events, Blade components, route registration, and explicit classes are the default extension mechanisms. Add a focused registry only if several actual consumers need runtime registration.
10. **No custom framework for modules.** Modules are first-party code integrated through ordinary Laravel service providers/configuration and documented extension points.

## 6. Data ownership and source-of-truth rules

1. Each domain/capability has one clear owner for writes, validation, and lifecycle transitions.
2. Shared relational data should use normal Eloquent relationships and database foreign keys when appropriate. Do not prohibit foreign keys merely to enforce artificial layer boundaries.
3. Cross-cutting records such as audit entries or revisions may refer to different subject types where that is required, but their polymorphic shape and retention policy must be explicit.
4. Media attachments use the selected media library's supported API and collections. Do not add duplicate media foreign-key columns solely to represent featured images, author photos, category images, or editor attachments.
5. Search indexes, rendered article HTML/text, cache entries, trending lists, and analytics aggregates are derived/read-model data. The owning source record or defined collection pipeline remains authoritative; document rebuild/purge paths.
6. Settings have one storage/API owner. Avoid simultaneously storing the same setting in a package-managed store and a custom table without a migration/synchronization rule.
7. Permission definitions are code-owned and versioned. Administrator-assigned role memberships/configuration must not be overwritten by routine deployments.
8. Migrations are the schema source of truth. Do not modify deployed migrations to disguise a schema change; follow the migration policy in the TRD.

### 6.1 Conflicts to resolve before the affected migrations are implemented

The current PRD and TRD still contain several potential source-of-truth conflicts. They are listed here deliberately rather than silently decided by this document:

- **Media — resolved by ADR-0001:** Spatie Media Library owns media records/conversions; NewsCMS keeps `media_folders`, a NewsCMS-owned link table, and `media_usages`. No duplicate authoritative media records.
- **Permissions — resolved by ADR-0002:** `spatie/laravel-permission` owns role/permission records; permission keys stay code-owned; seeding never overwrites administrator-configured roles.
- **Audit — resolved by ADR-0003:** `spatie/laravel-activitylog` is the authoritative store (+ optional read model); retention via `AUDIT_RETENTION_DAYS`.
- **Settings — resolved by ADR-0004:** `spatie/laravel-settings` typed store is the single owner; language-scoped keys and validation defined there.
- **Modules in v1:** PRD identifies LiveBlog, Galleries, and Polls but also leaves Polls open in §22 (Q8, Phase 4). Treat optional module scope as unconfirmed until the PRD decision is closed.
- **Content body format:** editor confirmed as TipTap (Q1, P0-T05). Finalize the body schema and stored derived fields before article migrations are frozen (P2-T04).

The four store conflicts above are closed by ADR-0001…0004; the remaining bullets track Q1/Q8. This architecture does not override those decisions.

## 7. Public site and theme boundary

The public site is server-rendered with Laravel Blade. The NewsCMS theme system is a small application subsystem built on Blade view resolution, Blade components, and Vite; it is not a second template language.

A theme owns presentation only:

- `theme.json` metadata and validated, typed presentation options.
- Blade templates, layouts, theme assets, and optional theme-specific language resources.
- Presentation slots and approved homepage/widget layouts.
- Responsive styling, typography, and presentation of prepared content.

A theme does **not** own article lifecycle, authorization, SQL queries, search behaviour, publication rules, or application data. Controllers, query services, or small view models prepare the data the theme receives.

View resolution is deterministic: active theme view → shipped default theme view → safe fallback/error view. A missing or invalid theme must not blank the public website. Theme metadata is data and must never execute PHP. Theme assets are built through Vite in CI/release tooling; production hosts serve built assets and do not need Node.js.

The shipped default theme should meet the accessibility, responsive, Indic typography, configured RTL, and performance requirements defined in the PRD/TRD. Child themes, a theme marketplace, a drag-and-drop builder, and arbitrary executable theme extensions are out of scope for v1.

## 8. Admin boundary

The admin is a custom Laravel Livewire + Blade application using Flux UI components and Alpine.js where needed. Flux supplies UI components; it is not the admin framework or source of business rules.

- Authentication, policies, gates, and permission checks are enforced server-side.
- Every Livewire public action validates input and authorizes the operation, even if the UI hides the control.
- Shared UI components standardize forms, tables, filters, pagination, confirmation, empty/loading/error states, and accessibility.
- Admin screens call application actions, queries, and policies; they must not bypass capability ownership or duplicate domain rules.
- Do not introduce Filament or a generic custom admin framework unless the product decision is explicitly changed.

## 9. Optional first-party modules

Modules are optional feature areas, not a general third-party plugin ecosystem. Current candidates are LiveBlog, Galleries, and Polls; their inclusion in v1 depends on the PRD's scope decisions.

A module may contain its own models, migrations, actions, policies, jobs, routes, Livewire screens, views, and tests. It must declare its dependencies and required permissions/settings clearly and integrate through Laravel service providers/configuration and documented application extension points.

Rules:

1. Module code remains inside the application repository for v1; it is not automatically a Composer package.
2. No third-party/customer-installed module marketplace.
3. No dynamic code installation from the admin panel.
4. A module may not bypass authorization or write another capability's tables directly.
5. Disabling a module, if runtime enable/disable is retained, must hide/deactivate its routes and UI without silently deleting its data. Do not implement dynamic dependency graphs or automatic migration rollback.
6. The main editorial workflow and public pages must continue to work when an optional module is disabled, except for routes/templates that explicitly belong to that module.

## 10. Packages and Laravel-native functionality

The TRD owns the package inventory, version verification, licensing/maintenance review, and phase gates. This architecture defines the principle:

- Prefer Laravel's built-in facilities where they meet the requirement.
- Use a focused Composer package for commodity functionality when the compatibility and maintenance review passes.
- Do not install every candidate at project kickoff; install a package when its capability is being implemented.
- Do not wrap or replace Laravel's service container, routing, events, queues, scheduler, cache, migrations, or Artisan.
- Do not turn application capability folders into packages without a demonstrated need for independent reuse or release.

Planned package candidates are currently documented in TRD §2. They include Media Library, Permission, Settings, Activitylog, and potentially Scout/Meilisearch and phase-gated sitemap, backup, health, response-cache, error reporting, storage, and SEO integrations. The package/table conflicts in §6.1 must be resolved before implementation.

## 11. Architecture enforcement and tests

Architecture rules must protect concrete risks rather than enforce a theoretical framework. Keep these checks in the ordinary Laravel test/static-analysis pipeline:

- Critical business rules are not implemented in Blade templates or UI-only JavaScript.
- Public queries apply publication status, language, and visibility constraints consistently.
- Authorization is enforced in policies/server-side actions and tested for each role.
- Capability code does not directly write another capability's tables.
- Known forbidden circular dependencies are prevented where practical by tests/static analysis.
- Theme resolution has deterministic fallback tests and validates theme configuration.
- Module enablement/registration behaviour is tested if runtime toggling is retained.
- Publishing triggers required cache invalidation, search updates, and notifications.
- Migrations, media processing, revisions/conflict handling, RSS/sitemap output, redirects, and public cache/cookie rules have focused tests.

Use Pest, Larastan, Pint, Vitest, Playwright, and k6 as selected in the TRD, with exact compatible versions pinned at implementation kickoff. Do not require a separate standalone test harness for each capability. Add architecture rules incrementally when a real violation or repeated risk demonstrates their value.

## 12. Request and event flows

### 12.1 Article publish

1. Admin UI submits a validated request/action.
2. Server-side authorization and article validation run.
3. The article state transition and revision/concurrency rules are applied in a transaction where appropriate.
4. A Laravel event is dispatched for the meaningful publication transition.
5. Listeners/jobs perform side effects such as cache/CDN purge, search indexing, notifications, and analytics/discovery refresh as required.
6. Each side effect is retry-safe and observable; a failed side effect must not silently corrupt the authoritative article record.

### 12.2 Media upload

1. Validate user permission, file size/type, and upload constraints.
2. Store through the configured Laravel filesystem and selected Media Library integration.
3. Queue expensive conversions/metadata processing.
4. Track processing state and surface failures in the admin.
5. Update the NewsCMS media usage/index data only where it serves a distinct documented purpose; do not duplicate package-owned attachment truth.

### 12.3 Public request

1. Laravel route/middleware resolves the public request and language.
2. A query/service fetches only content that is publicly visible and published.
3. The handler prepares a view model or equivalent data structure.
4. The active theme renders the Blade view, with deterministic default-theme fallback.
5. Cache headers, cookie restrictions, SEO metadata, and safe content rendering follow the PRD/TRD.

These flows are architectural outlines. Detailed sequencing, class names, cache keys, job queues, and endpoint definitions belong in the TRD and implementation plan.

## 13. Repository and deployment model

- One Laravel application and one primary source repository.
- One site/application instance per news publication in v1; no multi-tenancy.
- Environment-specific secrets and infrastructure settings remain in environment/configuration, not source control or the database.
- Use Laravel's normal dependency installation, migration, cache, queue, and scheduler mechanisms.
- Use ordinary Laravel Artisan commands first. Add a NewsCMS-specific command only when it provides concrete validation or a repeatable application-specific workflow.
- Deployment, backup, restore, worker, scheduler, CDN, and health procedures are defined in TRD §21 and PRD §21. This document does not introduce a custom installer, upgrade framework, or fleet orchestrator.

## 14. Architecture decisions and change control

The following decisions are fixed unless the PRD/TRD and this document are explicitly revised:

1. NewsCMS is one normal Laravel application, not a full Composer package.
2. Laravel remains the framework; no parallel runtime framework is built.
3. Capability boundaries are conceptual/application-level boundaries, not package-per-capability requirements.
4. The admin is custom Livewire + Blade + Flux UI; no Filament dependency.
5. The public theme system uses Blade and Laravel view resolution.
6. Modules are first-party and application-local in v1.
7. Media attachment ownership must follow the selected Media Library integration; conflicting schema definitions must be reconciled before migrations.
8. Architecture enforcement uses focused tests/static analysis in the ordinary application suite.

Changes that affect product behaviour must update the PRD. Changes that affect implementation constraints must update the TRD. Changes to ownership, dependency direction, or the canonical codebase structure must update this document. Record significant cross-document choices in a small ADR rather than adding a custom governance framework.

## 15. Open architecture follow-ups

These are explicit follow-ups, not permission to guess:

- Closed: §6.1 store conflicts resolved by ADR-0001…0004 (P0-T04).
- Editor confirmed (TipTap, Q1 in P0-T05); body schema due before article migrations are frozen (P2-T04).
- Confirm v1 scope for Polls and other optional modules (Q8, Phase 4 — still open).
- Closed: shared `app/Models` namespace convention (P0-T03).
- Confirm whether runtime module enable/disable is truly required for v1 or whether optional module registration at deploy time is sufficient.
- Closed: TRD §3.2 contains no directory tree (verified P0-T03); this tree is canonical.

---
