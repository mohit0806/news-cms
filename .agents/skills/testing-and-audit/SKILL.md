---
name: testing-and-audit
description: Use for NewsCMS behavior changes, bug fixes, refactoring, test writing, security-sensitive work, code reviews, verification, and P0–P5 phase-completion audits.
---

# NewsCMS Testing and Audit

## Purpose

Prove that NewsCMS changes satisfy their documented requirements and preserve important behavior. Use focused, meaningful tests and honest evidence; do not create unnecessary testing infrastructure.

## Mandatory preparation

1. Read root `AGENTS.md` and applicable repository instructions.
2. Read the `testing-best-practices` skill before writing tests, if available.
3. Read the relevant task in `docs/MASTER_PLAYBOOK.md`, its acceptance criteria, and cited PRD/TRD/architecture sections.
4. Inspect existing tests, factories, helpers, test configuration, and sibling test patterns.
5. Verify available test commands and package versions before relying on their behavior.
6. Load `newscms-architecture` when checking architecture, data ownership, or shared contracts; load `phase-execution` for planned work and phase gates.

## Testing principles

- Test externally observable behavior and meaningful regression risks.
- Prefer feature tests for application workflows; use unit tests for isolated logic where useful.
- Follow the installed Pest version and repository conventions.
- Use factories and established factory states for model setup.
- Test server-side authorization and validation, not only hidden or disabled UI controls.
- Cover the successful path and relevant failure cases, such as invalid input, denied access, missing records, duplicate operations, stale updates, and important boundary conditions.
- Do not over-test implementation details or add unrelated tests.
- Never delete, skip, or weaken tests just to obtain a green result without explicit approval.
- Do not create verification scripts or use Tinker when the existing test suite can prove the behavior.

## Verification commands

Use the narrowest test set that covers the change first. Examples, subject to the repository's actual configuration:

- `php artisan test --compact path/to/RelevantTest.php`
- `php artisan test --compact --filter='relevant test name'`
- `vendor/bin/pint --dirty --format agent` after PHP files change.
- `composer test` for the aggregate quality suite when appropriate.

Before relying on `composer test`, inspect the current Composer scripts. The playbook describes it as running configuration clear, Pint test mode, PHPStan, and Artisan tests; confirm the current definition rather than assuming it has not changed. If using a command that conflicts with a more specific active Boost rule, follow the active project rule and report the difference.

Run configured static analysis, architecture tests, frontend builds, security audits, Playwright, or load checks when the task or phase requires them and the tools are configured. Do not invent commands for absent tools.

After focused tests pass, report whether the broader suite also ran. Distinguish a product/test failure from an environment limitation only when evidence supports the distinction.

## NewsCMS-specific audit checks

Apply relevant checks from the active task and playbook, including:

- Permission matrix: every relevant admin route and Livewire action is authorized.
- Editorial lifecycle: draft, review, schedule, publish, preview, trash, and concurrency behavior when touched.
- Data integrity: migrations, uniqueness, foreign keys, ownership, deletion, and rollback risks.
- Hindi/Devanagari slug and search corpora where relevant.
- Media validation, deduplication, conversion, and safe file handling where relevant.
- Canonical URLs, redirects, SEO, feeds, and unpublished/noindex exclusion where relevant.
- Public caching and purge behavior; public responses must not set cookies.
- Theme boundaries: themes render prepared data and do not perform application queries or authorization.
- Embed safety and click-to-load guarantees where relevant.
- Analytics bot filtering, deduplication, and cache compatibility where relevant.
- Security, accessibility, responsive behavior, performance, and operational checks required by the task.

Do not apply every item mechanically to every change. Select the checks relevant to the task without overlooking material risks.

## Phase audits

Use the phase exit gate in the current Master Playbook, not a generic definition of completion.

For an audit:

1. Read the phase objective, task list, requirements, dependencies, and exit criteria.
2. Map each required task to implementation and evidence.
3. Check actual test and quality results.
4. Identify untested behavior, unresolved defects, architectural violations, and documentation drift.
5. Classify findings by severity and state whether they block the phase.
6. Check that deferred work and assumptions are explicitly recorded.
7. Recommend PASS, PASS WITH FINDINGS, or FAIL only when evidence supports the result.

The playbook treats Critical findings as blocking and High findings as blocking unless accepted; follow any more specific approved gate criteria. Do not conceal a blocker in a general summary.

## Required report

- Task/phase and scope audited.
- Requirement and acceptance-criteria references.
- Checks run, commands, and actual outcomes.
- Findings grouped by severity with evidence and impact.
- Required checks not run and why.
- Remaining risks, deferrals, and next actions.
- Overall result and justification.

Never fabricate test output, coverage, security assurance, browser verification, or phase acceptance.
