# ADR-0002 — Permissions storage source of truth

**Status:** Accepted 2026-10-09 (P0-T04; owner direction D-P0-1).
**Affects:** PRD §4, §20; TRD §2, §6.5; ARCH §6.1.

## Context

TRD §2 planned `spatie/laravel-permission` while PRD §20 sketched custom `roles`, `permissions`,
and `role_permission` tables, and put a `role_id` FK on `users` (ARCH §6.1).

## Decision

1. **`spatie/laravel-permission` owns role/permission records** (package tables). PRD §20's custom
   `roles`/`permissions`/`role_permission` tables and the `users.role_id` FK are superseded.
2. **Permission keys stay code-owned and versioned** (PRD §4 permission matrix is the contract).
3. The product rule "each user has exactly one role" (PRD §4.1) is enforced in application logic,
   not by schema.
4. **Seeding never overwrites administrator-configured role assignments** (TRD §6.5): seeders add
   missing keys/roles idempotently; deployment-time sync is additive only.

## Alternatives considered

- **Custom role tables:** rejected — rebuilds a solved commodity, contradicts TRD §2 direction.
- **Multiple roles per user:** rejected — PRD §4.1 fixes single-role; package supports it, app enforces it.

## Consequences

- P1-T01 builds roles/permissions/policies on the installed package (install needs dependency-review
  approval). Permission-matrix test (TRD §7, §23.2) covers the catalog.
