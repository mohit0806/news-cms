# ADR-0004 — Settings storage source of truth

**Status:** Accepted 2026-10-09 (P0-T04; owner direction D-P0-1).
**Affects:** PRD §15.6, §20; TRD §2; ARCH §6.1.

## Context

TRD §2 planned `spatie/laravel-settings` while PRD §20 sketched a custom `settings` table with
`(group, key, language)` uniqueness (ARCH §6.1).

## Decision

1. **`spatie/laravel-settings` with typed settings classes is the single settings store.**
   PRD §20's custom `settings` table is superseded.
2. **Language-scoped settings** are represented as typed settings properties per language scope
   (not a parallel custom table); validation lives on the settings classes.
3. Cache invalidation follows the package's mechanisms plus the existing fragment rules (TRD §12.4,
   e.g. `settings:all` until change).

## Alternatives considered

- **Custom `settings` table:** rejected — second source of truth, contradicts TRD §2 direction.
- **Untyped key/value in package store:** rejected — loses the validation PRD §15.6 requires.

## Consequences

- P1-T03 builds admin settings screens on the installed package (install needs dependency-review
  approval). No setting is stored simultaneously in two stores (ARCH §6.1 rule 6).
