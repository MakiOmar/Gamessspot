# Route Testing Notes (Laravel 12)

## Status after Laravel 10 → 12 upgrade

Application routing works in the real HTTP kernel:

- `GET /` → 200
- `GET /up` → 200 (health via `bootstrap/app.php`)
- `GET /manager/login` → 200
- `GET /manager` → 302 (auth redirect)
- Schedule: `queue:work` every minute; `accounts:sync-secondary-stock` hourly

## PHPUnit quirk

Some Feature tests historically accepted 404 for public routes even when `php artisan route:list` shows them and the browser/kernel returns 200. This is a **test-environment** issue (often related to subdirectory `APP_URL` like `http://localhost/gamessspot`), not a production routing failure.

`phpunit.xml` now forces `APP_URL=http://localhost` for tests. Prefer kernel/browser smoke checks over relying solely on Feature route existence assertions when diagnosing routing.

## Bootstrap

Routing, middleware aliases (`admin`, `checkRole`), and health `/up` are configured in [`bootstrap/app.php`](bootstrap/app.php). Providers live in [`bootstrap/providers.php`](bootstrap/providers.php).
