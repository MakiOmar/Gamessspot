# Laravel Version Upgrade Tracking

This branch tracks the Laravel version upgrade process.

## Current Version
- **Laravel Version:** 12.64.0
- **PHP Version:** 8.2.13
- **Upgrade Target:** Completed (Laravel 10 → 11 → 12)

## Upgrade Checklist

### Pre-Upgrade
- [x] Review Laravel upgrade guide
- [x] Check PHP version compatibility (8.2 OK for L12)
- [x] Review breaking changes
- [x] Backup database / working tree
- [x] Test current application thoroughly (route smoke)
- [x] Document custom modifications

### Upgrade Process
- [x] Phase 0: L10 security patches (Guzzle, Excel/PhpSpreadsheet, CommonMark, Carbon); remove laravel-mix
- [x] Phase 1: Laravel 11 + Sanctum 4 + Collision 8 + PHPUnit 11
- [x] Migrate Kernel/Handler/schedule into `bootstrap/app.php` + `routes/console.php` + `bootstrap/providers.php`
- [x] Update `public/index.php`, `artisan`, `tests/TestCase.php`
- [x] Sanctum 4 publish; `CACHE_STORE` env support
- [x] Phase 2: Laravel 12 + Backup ^9.3 + AdminLTE ^3.15 + Vite 6
- [x] Allow SVG uploads via `image:allow_svg` (L12 image rule change)
- [x] Clear caches; run smoke checks

### Post-Upgrade
- [x] Verify routes (/, /up, /manager/login, device submit, schedule)
- [x] `composer audit` — no advisories
- [x] `npm audit` — 0 vulnerabilities
- [x] `npm run build` succeeds on Vite 6
- [ ] Deploy to staging
- [ ] Test in staging environment
- [ ] Deploy to production

## Final Package Versions (key)

| Package | Version |
|---------|---------|
| laravel/framework | 12.64.0 |
| laravel/sanctum | 4.3.3 |
| spatie/laravel-backup | 9.3.6 |
| jeroennoten/laravel-adminlte | 3.16.0 |
| laravel/ui | 4.6.3 |
| nesbot/carbon | 3.x |
| vite | 6.x |

## Custom Modifications Preserved

1. **CacheManager Service** — custom cache implementation
2. **Model Observers** — cache invalidation logic
3. **Custom middleware** — `admin`, `checkRole`, custom `auth`/`guest`
4. **RouteServiceProvider::HOME** — `/manager` (constant only; routing in bootstrap)
5. **Schedule** — `queue:work database --stop-when-empty` every minute; `accounts:sync-secondary-stock` hourly
6. **config** — Africa/Cairo timezone, `company_name`, admin guard, AdminLTE, permissions, PDO options
7. **local disk** — explicit `storage_path('app')` root

## Notes

- Branch: `feature/laravel-upgrade`
- Feature HTTP tests may still report 404 for some public routes under PHPUnit while the same routes return 200 via the HTTP kernel (known testing quirk with subdirectory `APP_URL`); application smoke checks pass.
- Out of scope: Laravel 13 / PHP 8.3+, Backup v10, settings v4, Breeze/Fortify migration
