---
name: newscms-architecture
description: Use for NewsCMS architecture, application structure, data ownership, database design, capability boundaries, shared contracts, significant refactoring, or decisions that could change the approved design.
---

# NewsCMS Architecture

## Purpose

Keep NewsCMS a simple, maintainable, standalone Laravel modular monolith. This skill governs architecture-impacting work; it does not authorize expanding the product or building a second framework.

## Mandatory preparation

1. Read the root `AGENTS.md` and follow its current repository-rule and skill-activation instructions.
2. Read `docs/MASTER_PLAYBOOK.md` for the current phase, task dependencies, and relevant task contract.
3. Locate and read the applicable sections of the canonical PRD, TRD, and `docs/ARCHITECTURE.md`. Use the actual repository paths if they differ.
4. Inspect relevant ADRs under `docs/adr/`, if present. The playbook says this directory may be created for the first material decision; do not assume an ADR exists.
5. Inspect the relevant implementation, sibling files, migrations, models, policies, tests, and dependency versions.
6. If Laravel or package APIs matter, verify installed versions and use Laravel Boost `search-docs` when available.

Do not claim to have inspected a file, schema, package version, tool result, or skill unless you actually did so.

## Non-negotiable architecture

- One standalone Laravel application per news publication, in one repository.
- No NewsCMS Core Composer package, fleet/provisioning platform, or multi-tenancy in v1.
- Laravel-native first: routing, middleware, validation, authentication, authorization, queues, scheduler, cache, migrations, and Artisan.
- Keep a modular monolith, not a custom framework.
- Do not introduce a custom kernel, DI container, event/command bus, engine registry, package-per-capability architecture, runtime module discovery, or generator framework unless an approved requirement proves it necessary.
- Follow the canonical `ARCHITECTURE.md` for directory structure, ownership, and boundaries. Do not invent a parallel architecture or duplicate the canonical directory tree in another document.
- Public pages must not set cookies, as specified in the playbook's non-negotiable boundaries. Check relevant PRD/TRD details before changing caching, analytics, tracking, or frontend behavior.
- Themes present prepared data and must not own application queries or authorization logic, according to the playbook's architecture boundary.

## Source-of-truth and decision gates

The Master Playbook identifies source-of-truth decisions for media, permissions, audit logging, and settings as P0-T04. Treat each decision as unresolved unless the current canonical documents or an approved ADR clearly show it has been closed.

In particular:

- The playbook states that Spatie Media Library is the media attachment source of truth and that duplicate media foreign-key columns must not be added without a documented product relationship.
- Do not assume the permissions, audit-log, or settings storage design is settled solely because a package is installed or mentioned.
- Before a consequential schema or ownership change, verify the current approved decision and record any conflict.
- Do not silently create duplicate tables, columns, models, or stores for data already owned by an approved package or subsystem.

When documents conflict, identify the exact statements and locations, check for an approved ADR, explain the consequences, recommend a minimal resolution, and stop before material or irreversible changes if unresolved.

## Architecture workflow

For each architecture-impacting task:

1. Identify the requirement and Master Playbook task ID, if applicable.
2. Determine the capability responsible for the behavior and data.
3. Inspect existing implementation and approved package capabilities.
4. Prefer the smallest Laravel-native solution.
5. Identify dependencies, callers, contracts, authorization, data lifecycle, failure modes, and migration implications.
6. List meaningful alternatives only where they affect the decision.
7. Define the tests and architecture checks needed to protect the design.
8. Update architecture documentation or create/update an ADR only when authorized or explicitly within scope.

## Phase awareness

Follow the active P0–P5 phase and its dependencies in `docs/MASTER_PLAYBOOK.md`. Do not bypass P0 decisions that the playbook says must close before dependent migrations or tasks. If the roadmap status is stale or the prerequisite evidence is absent, report that instead of assuming the gate passed.

## Required report

- Task ID and requirement references.
- Canonical documents and relevant sections inspected.
- Current ownership and architecture findings.
- Recommended design and why it is the smallest compliant option.
- Files or components likely affected, without inventing unsupported paths.
- Migration, compatibility, security, and test implications.
- Conflicts, assumptions, and approvals required.

For implementation work, also load `phase-execution` and `testing-and-audit` when applicable. For dependency changes, load `dependency-review`.
