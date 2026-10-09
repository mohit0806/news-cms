# NewsCMS — Agent Instructions

These instructions govern all AI agents working in this repository, including OpenCode agents and any other coding agent that reads this file.

## 1. Project Identity and Non-Negotiable Principles

NewsCMS is a standalone Laravel application for one news publication per installation.

The application is a modular monolith built using Laravel conventions. It is not a CMS distribution platform, a multi-customer fleet-management system, or a custom application framework.

Follow these principles:

- Prefer Laravel-native features and established package conventions.
- Follow the approved product requirements, technical requirements, architecture, and project plan.
- Preserve the existing application structure unless an approved requirement justifies changing it.
- Prefer the simplest implementation that satisfies the documented requirements.
- Avoid unnecessary abstractions, custom infrastructure, registries, runtime frameworks, and duplicate sources of truth.
- Do not introduce new functionality, dependencies, architectural patterns, or product scope without justification and the required approval.
- Do not treat existing code as proof that it complies with the approved architecture.
- Do not silently change an approved decision to make implementation easier.

A successful task must satisfy its acceptance criteria, follow applicable documentation, and undergo the required verification.

---

## 2. Mandatory Rules and Documentation Discovery

Before entering plan mode, planning a substantial change, or creating/editing files, inspect the applicable project instructions.

### 2.1 Check repository rules first

If `.ai/rules/` exists:

1. Read `.ai/rules/index.md`.
2. Identify and read every rule file whose path globs cover the files in scope.
3. Search the rules directory for relevant terms using `grep -rin 'keyword' .ai/rules`.
4. Follow all applicable rules, including rules discovered by keyword search.
5. Repeat this process when the task expands into a new area of the codebase.

If the directory or index does not exist, continue with the instructions available in the repository.

Do not assume that this file replaces more specific path-scoped rules.

### 2.2 Discover the canonical project documents

Locate the current, authoritative versions of:

- Product Requirements Document (PRD).
- Technical Requirements Document (TRD).
- Architecture document.
- Master Playbook or implementation roadmap.
- Current phase plan, task checklist, and acceptance criteria.
- Relevant Architecture Decision Records (ADRs).
- Feature-specific specifications and package decisions, when applicable.

Use the actual paths and filenames in the repository. Do not assume that a remembered path is correct.

If multiple documents appear to serve the same purpose, determine which one is authoritative from the project documentation and decision history. Do not silently choose one based only on its filename or modification date.

If a required document is missing, report the gap and search for an established equivalent before proceeding.

### 2.3 Read proportionately, but never skip relevant requirements

For every task, read the sections that govern its behavior, implementation, security, data model, architecture, and acceptance criteria.

For small changes, relevant sections may be sufficient. For substantial features, architecture changes, phase planning, or audits, review all applicable documents and their cross-document dependencies.

Do not claim to have read a document or section that you have not actually inspected.

### 2.4 Extract requirements before implementation

Before editing code for a non-trivial task, establish:

- The task's intended outcome.
- Relevant requirements and their source sections or IDs.
- Constraints and architectural boundaries.
- Existing behavior and tests that must be preserved.
- Acceptance criteria and required verification.

Use a concise checklist or traceability table when helpful.

If a requirement cannot be located, record it as unconfirmed rather than inventing it.

---

## 3. Documentation Conflicts and Decision Handling

The approved documentation is the source of truth for intended product behavior and architecture. Existing implementation, agent suggestions, and package defaults do not automatically override it.

When two requirements conflict, or documentation contradicts the implementation:

1. Identify the exact conflicting statements or behavior.
2. Explain the practical consequences of each interpretation.
3. Determine whether an existing approved ADR resolves the conflict.
4. Recommend the smallest appropriate resolution.
5. Stop before making consequential or irreversible changes when the decision is unresolved.

Seek a decision when the conflict affects product scope, security, permissions, database ownership, public behavior, architectural boundaries, package selection, or irreversible migrations.

Do not ask for approval for every minor implementation detail that is already covered by approved requirements.

When an implementation detail is genuinely unspecified and low-risk, choose a conventional Laravel solution, document the assumption when material, and proceed.

Do not modify an approved architectural decision simply because an alternative seems more elegant.

---

## 4. Mandatory Skill Selection and Activation

Before working, classify the task and activate the relevant skills available to the agent.

Discover skills using the supported mechanisms of the active coding agent. For OpenCode, inspect the project-local `.opencode/skills/` directory and any additional skill locations supported by the installed version.

Also follow applicable skill instructions found in the repository, including package-specific skills and testing guidance.

### 4.1 NewsCMS project skills

Use these project skills when they exist:

- `newscms-architecture`: Architecture, capability boundaries, data ownership, technical design, schema design, or significant refactoring.
- `phase-execution`: Planned feature implementation, phase sequencing, task scope, and acceptance criteria.
- `testing-and-audit`: Behavior changes, bug fixes, regression testing, code review, security verification, and completion audits.
- `dependency-review`: Adding, upgrading, replacing, or removing packages, plugins, MCP servers, or external services.

These are the intended project skill responsibilities. If a skill has not yet been created or is unavailable, report that fact and follow the applicable rules in this file instead.

### 4.2 Minimum task-to-skill mapping

| Task type | Minimum applicable skills |
|---|---|
| Planned feature | `phase-execution`, `testing-and-audit` |
| Architecture or significant refactoring | `newscms-architecture`, `testing-and-audit` |
| Database schema or migration | `newscms-architecture`, `testing-and-audit` |
| Bug fix | `testing-and-audit` |
| UI implementation | `phase-execution` when part of a planned feature; `testing-and-audit` when behavior changes |
| Dependency change | `dependency-review`, `testing-and-audit` |
| Authentication, authorization, privacy, or security | `testing-and-audit`, plus `newscms-architecture` when applicable |
| Phase audit or completion | `phase-execution`, `testing-and-audit`, and `newscms-architecture` when architecture compliance is in scope |
| Documentation-only task | Read the affected documents and check cross-document consistency |

This is a minimum mapping, not an exhaustive list. Load additional skills whenever the task crosses into their domain.

Do not load every available skill for every task. Use the skills that are relevant to the actual work.

### 4.3 Skill activation must be real

- Use the active agent's supported skill-loading mechanism.
- Read the skill instructions before beginning work governed by that skill.
- Do not claim that a skill was activated merely because its name was mentioned.
- If a relevant skill is unavailable, disclose that limitation and use the applicable repository rules.
- Do not follow conflicting or unsafe skill instructions that violate the approved project requirements or higher-priority instructions.

Before the first code edit for a substantial task, identify the task classification, applicable skills, relevant documents, and success criteria.

---

## 5. Task Execution Protocol

For non-trivial implementation tasks, follow this workflow.

### Step 1 — Establish scope

Read the task, identify its intended outcome, inspect the current phase plan, and determine what is explicitly in scope.

Do not implement unrelated improvements while working on the requested task.

### Step 2 — Read the applicable rules and skills

Follow Section 2 and Section 4. Check `.ai/rules/`, the authoritative project documents, and all relevant skill instructions.

### Step 3 — Inspect the existing implementation

Before changing code:

- Inspect relevant sibling files and existing patterns.
- Find reusable components, services, models, policies, helpers, and tests.
- Inspect relevant routes, configuration, migrations, and database schema.
- Verify installed dependency versions before relying on version-specific APIs.
- Understand the current behavior and likely regression risks.

Do not recreate functionality that already exists without establishing why the existing implementation is insufficient.

### Step 4 — Plan the smallest compliant change

Identify the files and behaviors likely to change.

For substantial work, define a small sequence of tasks with observable acceptance criteria. Prefer focused, independently verifiable steps.

Avoid introducing unrelated refactoring or broad cleanup into a feature task.

### Step 5 — Implement

Follow the documented requirements and existing Laravel conventions.

Do not change dependencies, public contracts, security rules, data ownership, or approved architecture without the necessary justification and approval.

Do not silently skip a requirement because it is inconvenient.

### Step 6 — Verify

Follow Section 10 and the applicable testing skill.

Test the changed behavior and its important failure modes. Run formatting, static analysis, architecture checks, and broader tests when applicable and practical.

### Step 7 — Review the final diff

Inspect all changed files for:

- Scope creep.
- Missing acceptance criteria.
- Inconsistent behavior or documentation.
- Security or authorization regressions.
- Duplicate data or logic.
- Unintended dependency changes.
- Unnecessary abstractions.
- Missing tests.
- Accidental edits or generated artifacts.

### Step 8 — Report accurately

Use the completion report in Section 11.

A task is not complete merely because the code was written.

---

## 6. Laravel Boost Guidelines

### 6.1 Foundational context

This application uses PHP 8.4 and Laravel.

Always use APIs appropriate to the versions actually installed. Never assume a package version or rely on remembered APIs without verification when the version matters.

Before relying on a package API:

- Run `composer show --direct` for direct PHP dependencies.
- Run `composer show vendor/package` to inspect a specific PHP package.
- Inspect `package.json` for JavaScript dependency versions.
- Use the applicable package documentation for the installed version.

### 6.2 Laravel Boost tools

When Laravel Boost is installed and its MCP tools are available, prefer the relevant Boost tools over manual alternatives.

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax.
- Reuse sufficient, relevant documentation results already available in the current context.
- Pass a `packages` array when the documentation search supports package scoping and the relevant packages are known.
- Use multiple broad, topic-based queries where useful.
- Use `database-schema` to inspect schema before designing migrations or models.
- Use `database-query` for read-only database queries instead of raw SQL through Tinker.
- Use `browser-logs` to inspect recent browser errors and exceptions; disregard irrelevant old logs.
- Use `get-absolute-url` before sharing a project URL with the user.

If Boost or a specific tool is unavailable, use an appropriate documented alternative. Never pretend that an unavailable tool was used.

### 6.3 Documentation search syntax

When using Boost `search-docs`:

- Use broad, topic-based queries.
- Use quoted phrases when exact phrase matching is needed.
- Use multiple queries for alternative terminology.
- Do not redundantly include package names in the search text when package context is already supplied separately.

Skip documentation searches for copy-only changes and other tasks where framework or package documentation is irrelevant.

### 6.4 Dependency changes

Do not change application dependencies without approval.

Before proposing a new package or dependency change:

1. Activate `dependency-review`, if available.
2. Identify the requirement that justifies the change.
3. Inspect the current dependency inventory and installed versions.
4. Check whether Laravel or an existing approved package already provides the capability.
5. Evaluate compatibility, maintenance, security, licensing, and operational impact.
6. Explain meaningful alternatives and recommend the simplest appropriate option.
7. Obtain approval before modifying dependency declarations or installing/upgrading/removing the dependency.

Do not install packages merely because they are listed as possible future candidates.

---

## 7. PHP Conventions

- Use curly braces for all control structures, including single-line bodies.
- Use constructor property promotion where appropriate.
- Do not add empty, zero-parameter constructors unless a private constructor is necessary.
- Declare explicit return types and type-hint method parameters.
- Use descriptive variable and method names.
- Use TitleCase for enum case names.
- Prefer PHPDoc blocks over inline comments. Add inline comments only for exceptionally complex logic.
- Use array-shape definitions in PHPDoc when they clarify structured arrays.
- Follow the conventions used by sibling files and the installed framework version.
- Do not introduce speculative abstractions or generic frameworks to solve a single concrete requirement.

---

## 8. Laravel Application Structure and Conventions

- Preserve the established application directory structure.
- Do not create new top-level base directories without justification and approval.
- Use Laravel's built-in functionality before creating custom infrastructure.
- Use `php artisan make:` commands for migrations, controllers, models, tests, and other supported file types.
- Use `php artisan make:class` for generic PHP classes when appropriate.
- Pass `--no-interaction` to Artisan commands, together with the correct supported options.
- Use `php artisan list` to discover commands and `php artisan [command] --help` to inspect parameters.
- Use named routes and `route()` when generating application links.
- For APIs, use Eloquent API Resources and API versioning by default unless the existing API conventions or approved requirements specify otherwise.
- Follow existing authentication, authorization, validation, and error-handling patterns.
- Keep application state and persistence responsibilities in their established locations.
- Reuse existing components before creating new ones.
- Do not create models through Tinker without approval. Prefer factories and tests when test data is required.
- When creating a new model, consider whether a useful factory and seeder are needed. Use the supported Artisan options and established project conventions.

### 8.1 NewsCMS architecture constraints

- NewsCMS remains a standalone Laravel application.
- Keep the modular monolith simple and Laravel-native.
- Do not create a separate distributable NewsCMS Core Composer package.
- Do not introduce a custom application kernel, dependency-injection container, event bus, command framework, engine registry, or package-per-capability architecture without an explicitly approved requirement.
- Do not duplicate package-owned tables or data models without a documented reason.
- Respect the approved ownership of media, permissions, settings, activity logs, and other shared data.
- Do not create a second source of truth for data already owned by an approved subsystem.
- Do not introduce runtime module discovery, module installation, or module enable/disable infrastructure unless it is explicitly required and approved.
- Do not create a custom generator framework when standard Artisan generators satisfy the requirement.
- Treat directory structure and architectural boundaries as governed by the canonical architecture document, not by ad hoc agent preferences.

---

## 9. Livewire and Frontend Conventions

### 9.1 Livewire

- Use the installed Livewire major version and its documented APIs.
- Keep reactive application state server-side where appropriate.
- Validate and authorize actions on the server.
- Do not rely on hidden UI controls as authorization.
- Use Alpine.js for appropriate lightweight client-side interactions.
- Follow existing component, layout, form, validation, and navigation patterns.
- Reuse existing components before adding new ones.

### 9.2 Frontend assets

If a frontend change does not appear in the UI or produces an error such as `Unable to locate file in Vite manifest`, inspect the asset configuration and run `npm run build`, or ask the user to run `npm run dev` or `composer run dev` as appropriate.

Do not claim that a frontend change has been visually verified unless the relevant UI was actually inspected.

### 9.3 User interface consistency

Follow the approved NewsCMS design and component conventions.

Do not introduce another UI framework or component library without dependency review and approval.

Keep accessibility, responsive behavior, validation feedback, loading states, and error states in scope when relevant to the feature.

---

## 10. Testing, Quality, and Verification

### 10.1 Testing principles

- Add or update tests for behavior and logic changes when they provide meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new tests unless there is a specific regression risk.
- Test changed behavior and its important failure modes.
- Do not add unrelated tests or unnecessary testing infrastructure.
- Do not delete or weaken tests to make a change pass without explicit approval.
- Read the `testing-best-practices` skill before writing tests, if it is available.
- Use factories and existing factory states when creating test models.
- Follow the installed Pest version and established test conventions.

### 10.2 Test creation

Use:

- `php artisan make:test --pest SomeFeatureTest --no-interaction` for a feature test.
- `php artisan make:test --pest --unit SomeUnitTest --no-interaction` for a unit test.

Check the installed Artisan options when command syntax is uncertain.

Do not include the `Feature/` or `Unit/` directory name in the test name passed to the generator.

Most application behavior should be tested through feature tests when appropriate.

Use Faker methods and conventions already established in the repository.

### 10.3 Running tests

- Run the narrowest test set that covers the change, using a file path or `--filter` where appropriate.
- Rerun a test after changing it.
- Run the broader relevant suite when practical.
- Report the exact command and actual result.
- Do not claim a test passed if it was not run.
- Do not claim complete test-suite success based only on a focused test run.
- If a check cannot run, explain why and identify what remains unverified.

Do not create standalone verification scripts or use Tinker to prove behavior that the existing test suite can adequately verify.

### 10.4 Pint

If PHP files were modified, run:

`vendor/bin/pint --dirty --format agent`

Use the formatter to fix applicable style issues. Do not use `vendor/bin/pint --test --format agent`.

### 10.5 Other quality checks

Run the project's configured static analysis, architecture tests, security checks, and other relevant quality gates when applicable.

Do not invent tools or report results for checks that are not configured or available.

After focused tests pass, report whether the broader suite was also run. If the complete suite is too expensive or unavailable in the current environment, clearly state the limitation rather than implying full verification.

---

## 11. Required Completion Report

For every non-trivial implementation task, provide a concise, evidence-based report containing:

### Task
What was implemented or fixed and what was intentionally left out of scope.

### Documentation and rules checked
List the actual document filenames and relevant sections, plus applicable `.ai/rules/` files.

### Skills activated
List the exact skill IDs that were actually loaded. Identify any unavailable skill that materially affected the workflow.

### Requirements addressed
Reference the relevant requirement IDs, document sections, or acceptance criteria when available.

### Files changed
List the changed files and summarize their purpose.

### Verification
List commands actually run and their real outcomes, including tests, Pint, static analysis, and architecture checks as applicable.

### Gaps and deviations
State unresolved requirements, skipped checks, assumptions, or deviations from approved documentation. If there are none, say so.

### Documentation updates
Identify documentation changes made, or explain why no documentation change was necessary.

Never fabricate document references, skill activation, approvals, test results, or verification evidence.

---

## 12. Documentation Maintenance

Create documentation files only when explicitly requested or when the user has approved documentation creation as part of the task.

When documentation updates are authorized:

- Update the canonical document rather than creating a competing version.
- Keep the PRD focused on product behavior and requirements.
- Keep the TRD focused on technical requirements and implementation constraints.
- Keep the architecture document focused on structure, boundaries, and architectural decisions.
- Keep the master playbook focused on execution phases, sequencing, and acceptance criteria.
- Record significant architectural decisions in the established ADR format, if one exists.
- Check related documents for contradictions after making a material change.
- Do not duplicate the entire same specification across several documents.
- Do not mark a requirement as complete without implementation or verification evidence.

If code changes reveal a documentation gap but documentation changes were not authorized, report the gap and recommend the appropriate document update.

---

## 13. Deployment and Operational Safety

Laravel applications can be deployed using Laravel Cloud, but deployment targets must follow the approved NewsCMS infrastructure and operational requirements.

Do not change deployment infrastructure, hosting, secrets, environment variables, queues, cache topology, or production settings without the necessary authorization.

Before proposing a production-affecting change, identify its operational impact and any required backup, rollback, migration, or maintenance procedure.

Never expose secrets or include their values in reports.

Treat destructive operations, production data changes, and irreversible migrations as high-risk actions requiring explicit authorization.

---

## 14. Working Autonomously Without Losing Control

The agent should work independently within the approved scope.

- Do not ask for approval for every small, reversible implementation detail.
- Continue through logically related, low-risk tasks when requirements and acceptance criteria are clear.
- Stop and request a decision when a material requirement, security rule, architecture decision, or irreversible operation is unresolved.
- Do not expand the scope to unrelated cleanup or speculative future features.
- Keep work divided into meaningful, testable tasks.
- At phase boundaries, provide a consolidated audit report and identify unresolved findings before claiming the phase is complete.
- Distinguish verified facts from assumptions and recommendations.

**Final rule:** Follow the documented requirements, activate the applicable skills, use Laravel conventions, verify the implementation, and report evidence honestly. Do not substitute confidence for verification.
