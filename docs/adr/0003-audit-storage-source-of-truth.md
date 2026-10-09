# ADR-0003 — Audit log storage source of truth

**Status:** Accepted 2026-10-09 (P0-T04; owner direction D-P0-1).
**Affects:** PRD §16, §20; TRD §2, §6.8; ARCH §6.1.

## Context

TRD §2 planned `spatie/laravel-activitylog` while PRD §16/§20 specified a custom `audit_logs` shape
(ARCH §6.1).

## Decision

1. **`spatie/laravel-activitylog` is the authoritative audit store** (package activity table).
   PRD §20's custom `audit_logs` table is superseded.
2. PRD §16's product requirements stand unchanged: audited events, actor/IP/user-agent capture,
   admin review UI, and retention via `AUDIT_RETENTION_DAYS` (TRD §6.8 prune job).
3. A custom read model is **not** created unless audit-query performance in P4/P5 proves it necessary;
   the package table is queried directly until then.

## Alternatives considered

- **Custom `audit_logs` table:** rejected — duplicates package truth for a commodity log.
- **Eager custom read model:** rejected as premature; gated on measured need.

## Consequences

- P1-T04 wires audit events to the installed package (install needs dependency-review approval).
  Retention/reporting behavior is unchanged from PRD §16.
