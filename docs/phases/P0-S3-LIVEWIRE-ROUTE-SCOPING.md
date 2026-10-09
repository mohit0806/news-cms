# Spike S3 — Livewire routes under `/{ADMIN_PATH}` with scoped session cookie

**Task:** P0-T06. **Status:** COMPLETE — verdict: **feasible, adopt scoped cookie** (TRD §26-S3).
**Date:** 2026-10-09. **Stack:** Laravel 13.35.0, Livewire 4.4.7 (verified).

## Question

Can Livewire's update/upload/script endpoints live under `/{ADMIN_PATH}` so the session cookie
(`cms_session`, path `/{ADMIN_PATH}`) is never sent on public URLs (TRD §5.2, PRD D12)?

## Method

1. Source inspection of Livewire 4 endpoint registration (`HandleRequests`, `EndpointResolver`,
   `SupportFileUploads`, `FrontendAssets`, `FileUploadConfiguration`, `FileUploadController`).
2. Live prototype (reverted afterwards): `SESSION_COOKIE=cms_session` + `SESSION_PATH=/admin` in `.env`;
   `Livewire::setUpdateRoute(fn ($handle) => Route::post('/admin/livewire/update', $handle)->name('admin.livewire.update'))`
   in `AppServiceProvider::boot()`; temporary Pest test (2 tests, since deleted).
3. Observed: `route:list` output, login `Set-Cookie` header, custom-route URI/middleware — on MariaDB 10.11
   (SQLite PDO unavailable on this host).

## Observed results

- `POST admin/livewire/update` registered as `admin.livewire.update` with `web` middleware
  (auto-ensured by `setUpdateRoute()` together with `RequireLivewireHeaders`). Custom routes named
  `*.livewire.update` take runtime priority over the default route (`findUpdateRoute()`).
- Login response sets `cms_session @ /admin` — cookie path scoping works via stock `config/session.php`.
- Default key-prefixed route (`livewire-<hash>/update`) is still registered (provider boot runs before
  route files) but loses at runtime; without the cookie it cannot authenticate — safe by construction.
- Upload (`upload-file`) and preview endpoints use the hardcoded key-derived prefix and are **not**
  relocatable via public API — but `FileUploadController` uses no session/auth (default middleware is
  `throttle:60,1` via `FileUploadConfiguration`), so the scoped cookie does not break uploads.
- Script/js/css asset endpoints are stateless GETs; fine at root paths.

## Verdict

**Adopt the scoped cookie** (`SESSION_COOKIE=cms_session`, `SESSION_PATH=/{ADMIN_PATH}`) with the update
endpoint relocated under the admin prefix in P1-T02. Fallback (`cookie path /` + CDN bypass on
`cms_session`) **not needed**.

## Limitations and constraints for P1-T02

1. If `livewire.temporary_file_upload.middleware` is ever customized to include `web`/auth, uploads would
   break under the scoped cookie — keep uploads sessionless or revisit.
2. `ADMIN_PATH` default (`admin`) to be fixed in P1-T02 alongside the route relocation.
3. Public `cms.public` group + `AssertNoCookies` safety net are P1/P3 work, not part of this verdict.
4. Prototype changes fully reverted (`.env` and `AppServiceProvider.php` byte-identical to pre-spike
   backups); no production code changed.
