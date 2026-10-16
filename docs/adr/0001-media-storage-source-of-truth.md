# ADR-0001 — Media storage source of truth

**Status:** Accepted 2026-10-09 (P0-T04; owner direction D-P0-1, U1 link-table decision).
**Affects:** PRD §3.1.3, §7.1, §20; TRD §2, §6, §10; ARCH §6.1.

## Context

PRD §3.1.3 names Spatie Media Library as the source of truth for media records/attachments, while
PRD §20 sketched custom `media`/`media_conversions` tables — a duplicate-authority conflict (ARCH §6.1).
Separately, PRD §7.1 requires nested media folders, folder-scoped search/filter, and a storage usage meter,
and Media Library provides no hierarchical folders.

## Decision

1. **Spatie Media Library owns media records and conversions** (package tables). PRD §20's custom
   `media`/`media_conversions` tables are superseded and must not be created.
2. **NewsCMS keeps three owned structures:** `media_folders` (nested folders, PRD §7.1),
   a NewsCMS-owned link table `media_folder_media(folder_id, media_id, sort)` (U1 decision), and
   `media_usages(media_id, usable_type, usable_id)` (usage index, kept in sync on save).
3. The folder↔media link **never touches package-owned columns**. Folder-scoped queries join the link
   table; the usage meter reads package conversion rows + the link table.
4. Attachments (featured/OG/category/author/editor) use the library API + collections per the PRD §20
   media relationship rule — no `*_media_id` FK columns.

## Alternatives considered

- **Custom `media` tables for everything:** rejected — duplicates package truth, contradicts PRD §3.1.3.
- **Folder id in library custom properties:** rejected (U1) — weaker querying/ordering, couples NewsCMS
  taxonomy to package internals.

## Consequences

- P2-T05 implements folders/link/usage against the installed package (install needs dependency-review
  approval). Folder behavior in PRD §7.1 is fully preserved.
- If the package is ever replaced, only the record/conversion layer migrates; folders/usage survive.

## Addendum 2026-10-16 (P2-T05.1, owner-approved install)

- Installed `spatie/laravel-medialibrary:11.23.9` + `intervention/image:3.11.10` (MIT; dry-run showed
  6 installs / 0 updates / 0 removals; `composer audit` clean).
- Table-layout check (TRD §10 naming): v11 keeps a **single `media` table** (`id` bigint unsigned,
  `model` morph, `collection_name`, `manipulations`/`generated_conversions`/`responsive_images` JSON).
  There is **no separate `media_conversions` table**; TRD §10 references to conversion rows map to the
  `generated_conversions` JSON column. No schema decision changes: the package still owns
  records + conversions, NewsCMS still owns folders/link/usage.
- `media_usages.media_id` (`unsignedBigInteger`, no FK) matches the package PK type.
- Conversion queue routing stays at package defaults until T05.3 wires `MEDIA_QUEUE`.
