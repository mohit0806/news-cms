---
name: phase-execution
description: Use when planning or implementing an approved NewsCMS roadmap task, feature, phase, milestone, acceptance criterion, or phase-gate review.
---

# NewsCMS Phase Execution

## Purpose

Execute the canonical NewsCMS Master Playbook in small, ordered, verifiable tasks. Preserve approved scope, dependencies, and phase gates.

## Mandatory preparation

1. Read the root `AGENTS.md` and follow its repository-rule instructions.
2. Read `docs/MASTER_PLAYBOOK.md` and identify the active phase and exact task ID. If the canonical filename differs, locate the actual one.
3. Read the task's requirements, dependencies, entry conditions, acceptance criteria, verification instructions, and phase exit gate.
4. Read the relevant PRD, TRD, architecture, and ADR sections cited by the task.
5. Inspect the current code, tests, configuration, and package versions relevant to the task.
6. Activate additional skills as needed:
   - `newscms-architecture` for architecture, ownership, contracts, or schema design.
   - `testing-and-audit` for behavior changes, regression checks, or phase gates.
   - `dependency-review` for package or external-service changes.

Do not assume the playbook's example status or older reports prove a task or gate is currently complete. Look for current evidence.

## Roadmap and gate discipline

The playbook's phase sequence is:

- P0 — Baseline and decisions.
- P1 — Foundation and admin shell; depends on P0.
- P2 — Editorial core; depends on P0 and P1.
- P3 — Public site and themes; depends on P0 and P2.
- P4 — Newsroom features and growth; depends on P2 and P3.
- P5 — Operations and pilot; depends on P0–P4.

Use the current playbook as the authority for exact task ordering, conditions, and exit criteria. Do not begin a dependent task while its required prerequisite or decision remains open.

P0 is not a generic suggestion: its toolchain, CI/quality gates, architecture alignment, source-of-truth decisions, Q decisions, and spikes gate later work as specified in the playbook. In particular, respect P0-T04 and any task-specific dependencies before dependent migrations or features.

## Scope control

- Implement only the approved task and necessary supporting changes.
- Do not move future-phase work forward unless a real dependency requires it and the reason is recorded.
- Do not bundle unrelated cleanup, broad refactoring, or speculative features.
- Do not add requirements that are absent from the PRD.
- If the playbook and PRD conflict, record the conflict and resolve it through the documented process; do not silently choose.
- Work autonomously on routine, reversible details already covered by approved requirements. Request a decision for material unresolved product, security, data ownership, architecture, dependency, or irreversible migration choices.

## Task execution workflow

### 1. Establish scope

Identify the task ID, phase, desired outcome, requirement references, dependencies, and acceptance criteria. Confirm that prerequisites are satisfied by current evidence.

### 2. Inspect

Inspect sibling patterns, routes, components, models, policies, migrations, schema, tests, config, and dependency versions as relevant. Reuse existing code where appropriate.

### 3. Plan

Choose the smallest coherent implementation. Identify likely files and focused verification commands. Do not invent paths that are not supported by the repository.

### 4. Implement

Use Laravel-native patterns and the installed versions of APIs. Use supported Artisan generators with `--no-interaction` and correct options. Do not change dependencies without approval. Keep changes task-scoped.

### 5. Verify

Run the narrowest meaningful tests and task-specific checks. If PHP changed, run `vendor/bin/pint --dirty --format agent`. Run broader relevant tests, static analysis, architecture checks, build checks, or security checks when required by the task and available. Follow `testing-and-audit`.

The playbook lists `composer test` as the aggregate quality command; inspect the current `composer.json` before relying on its current definition. Do not assume that running one command covers additional task-specific acceptance checks.

### 6. Review and record

Inspect the final diff against the task scope and acceptance criteria. Update task status only to the level supported by evidence. Record commands and actual outcomes; distinguish Implemented, Verified, and Accepted.

## Status rules

Use the playbook's status vocabulary: Planned, In progress, Implemented, Verified, Accepted, Blocked, Deferred, and Rejected/out-of-scope.

- Implemented means the change exists.
- Verified means the required evidence has been obtained.
- Accepted means the relevant acceptance or phase gate has been satisfied.
- Blocked means a specific prerequisite, decision, or environment issue prevents safe progress.

Never mark a task verified from code presence alone. Never mark a phase accepted unless its exit gate is satisfied or an authorized decision explicitly accepts the remaining findings.

## Required task report

- Phase and task ID.
- Scope and requirements addressed.
- Prerequisites and gate evidence checked.
- Documents/rules and skills actually loaded.
- Files changed.
- Verification commands and actual outcomes.
- Deviations, assumptions, blockers, and unresolved decisions.
- Documentation or progress updates made or recommended.
