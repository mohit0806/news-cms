---
name: dependency-review
description: Mandatory before proposing or performing NewsCMS PHP/JavaScript package changes, plugin installation, upgrades, removals, MCP additions, or new external services.
---

# NewsCMS Dependency Review

## Purpose

Keep NewsCMS's dependencies small, compatible, maintainable, secure, and justified. A dependency must satisfy an approved requirement; popularity or speculative future usefulness is not sufficient.

## Approval requirement

Root `AGENTS.md` states that application dependencies must not change without approval.

- You may inspect dependencies and prepare a recommendation.
- Do not edit Composer/package manifests or lockfiles, install, upgrade, replace, or remove dependencies until the required approval is granted.
- If the user approves one package/version proposal, do not silently substitute a different package, major version, or external service.

## Mandatory preparation

1. Read root `AGENTS.md` and applicable repository rules.
2. Read the relevant Master Playbook task and PRD/TRD/architecture/ADR sections.
3. Inspect `composer.json`, `composer.lock`, `package.json`, and the relevant JS lockfile, plus installed versions where applicable.
4. Check whether Laravel or an existing approved package already provides the capability.
5. Use Laravel Boost `search-docs` for Laravel ecosystem behavior or version-specific APIs when available.
6. Research official package documentation and verifiable compatibility, maintenance, security, licensing, and operational requirements.
7. Load `newscms-architecture` if the choice affects architecture, persistence, data ownership, or runtime boundaries.
8. Load `testing-and-audit` to define verification and regression coverage.

Do not claim to have checked compatibility, maintenance, licensing, advisories, or official documentation unless the evidence was actually inspected.

## Evaluation checklist

For each candidate, document:

- **Requirement:** The specific approved requirement or task it satisfies.
- **Capability and limits:** What it does and does not provide.
- **Existing alternatives:** Laravel-native functionality and installed approved dependencies.
- **Alternatives:** Credible options, including no new dependency.
- **Compatibility:** PHP, Laravel, Livewire, Tailwind, Flux UI, or other relevant versions.
- **Maintenance/security:** Support status, release activity, known concerns, and relevant advisories where verifiable.
- **License:** License and material restrictions.
- **Operational impact:** Services, credentials, environment variables, storage, queues, scheduler, deployment, monitoring, and backups.
- **Data ownership:** Tables, migrations, files, indexes, source-of-truth responsibilities, and duplicate-storage risk.
- **Testing/migration:** Required tests, upgrade path, rollback, and data migration.
- **Recommendation:** Approve, reject, defer, or investigate further, with concise rationale.

## NewsCMS constraints

- NewsCMS itself remains a standalone Laravel application; do not turn it into a Composer-distributed CMS package.
- Individual Composer packages are permitted when justified and approved.
- Do not add package-per-capability architecture or a custom framework.
- Avoid duplicate ownership for media, permissions, settings, activity logs, or other shared data.
- Spatie Media Library is identified in the Master Playbook's non-negotiable boundaries as the media attachment source of truth. Do not propose a competing media store or duplicate media foreign-key columns without a documented product relationship and approved decision.
- The playbook identifies media, permissions, audit logging, and settings as source-of-truth decisions associated with P0-T04. Verify which decisions have actually been approved before relying on them.
- Search engines, image processing, backups, health monitoring, SEO helpers, sitemap generation, and caching packages are candidates only when an approved requirement justifies them.
- Do not install all possible candidates in advance.
- Verify current package compatibility against the actual installed stack. Do not rely on remembered or assumed versions.

## After approval

1. Reconfirm the exact approved package and version constraints.
2. Make only the approved dependency change.
3. Review manifest and lockfile diffs for unexpected changes.
4. Run relevant tests, formatting, static analysis, and package-specific checks.
5. Check configuration, security, deployment, and operational requirements.
6. Update documentation only when authorized or in scope.
7. Report what changed and the verification evidence.

If the proposed solution changes materially during implementation, stop and request approval for the revised choice.

## Required review report

- Requirement and task ID.
- Existing capabilities and alternatives checked.
- Package/service and proposed version constraints.
- Compatibility, maintenance, security, license, and operational findings.
- Data ownership and architecture impact.
- Recommendation and rationale.
- Approval status.
- Verification and migration/rollback plan.

Never claim a dependency was approved, installed, audited, or verified when it was not.
