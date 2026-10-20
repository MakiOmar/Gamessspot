# Next Load gift card supplier

Accounts can buy gift card codes from the [Next Load](https://stg.next-load.com) merchant API at sale time. POS, the storefront and the manager sell screen keep calling `POST /api/orders/receive` exactly as before; Accounts decides per card category whether the code comes from local stock or from Next Load.

Gift cards only. Subscriptions (PS Plus, etc.) stay shared game accounts.

## Setup

1. Add the credentials to `.env` (server-side only; never in POS, the storefront or the mobile app):

   | Key | Default | Notes |
   |-----|---------|-------|
   | `NEXTLOAD_ENABLED` | `false` | Sales and scheduled jobs use Next Load only when `true` **and** the three credentials are set. |
   | `NEXTLOAD_BASE_URL` | `https://stg.next-load.com/APIS/V1/` | Production URL is given when the account is activated. |
   | `NEXTLOAD_EMAIL` / `NEXTLOAD_PASSWORD` / `NEXTLOAD_SECURITY_CODE` | — | Merchant API credentials. |
   | `NEXTLOAD_TIMEOUT` | `30` | Seconds per request. |
   | `NEXTLOAD_LOW_BALANCE` | `0` | Warn (log + activity log, at most every 6 h) when the balance is below this. `0` disables. |
   | `NEXTLOAD_EXCHANGE_RATE` | `1` | Local currency units per 1 supplier unit. Converts supplier prices into card `cost` and the loss guard. |
   | `NEXTLOAD_ALLOW_LOSS` | `false` | When `false`, a code is never bought if its converted supplier price is above the category sale price. |

2. Recreate the Accounts containers (env is read at start), run `php artisan migrate --force`.
3. Manager → **Gift Cards → Next Load Supplier** (permission `manage-next-load`, granted to admin by default): **Test connection**, then **Refresh price list**.
4. **Products & links** → pick a product → link it to an existing category or create one, and choose the sourcing mode.

## Sourcing modes (per category)

| Mode | Behaviour |
|------|-----------|
| `local_only` (default) | Only codes added by staff. Never calls Next Load. |
| `local_first` | Sell a local code; buy from Next Load when none is left. |
| `supplier_only` | Always buy the code from Next Load at sale time (local codes are ignored). |

Listings (`GET /api/card-ctegories/list`) and `POST /api/orders/check_card_stock` include supplier-backed categories while the cached balance covers the product price. See [README_API.md](../README_API.md).

## Purchase flow and guarantees

1. A `next_load_orders` row with a unique `reference` is saved **before** `CreateOrder` is sent.
   - Shop / POS lines: `sf-{hash(storefront_order_id|line_key)}-{n}` — retries of the same line reuse the open or completed order.
   - Manager sales: `acc-{ulid}`.
2. `Complete` → codes are stored in `next_load_order_items` (serial and pin encrypted), the sold code becomes a `cards` row (`source = next_load`, encrypted) and the order is created.
3. `InProgress` → `/api/orders/receive` answers `202 {pending: true}`. `nextload:poll` follows the order with `FetchOrder`. A shop retry with the same line key then returns the code; a manager sale's code goes to stock.
4. `Failed` → nothing was charged; the next retry gets a new reference (`-2`, `-3`, …).
5. Timeout / no envelope → the order stays `pending_send` and is resent with the **same** reference. `reference is existing` is recovered through `Orders` + `FetchOrder`; it is never bought twice.
6. Token: cached encrypted; `Invalid Login` (HTTP 501) gets a new token and retries once.

Amounts are 3-decimal strings compared as integer thousandths (`App\Services\NextLoad\Money`), never floats.

## Scheduled jobs (`routes/console.php`)

| Command | Schedule | Purpose |
|---------|----------|---------|
| `nextload:poll` | every minute | Resend timed-out orders, poll `InProgress`, move orphaned manager / restock codes to stock. Gives up after 60 attempts (order flagged for manual review). |
| `nextload:balance` | every 15 min | Cache the balance; low-balance warning. |
| `nextload:sync-products` | hourly | Refresh `supplier_cost` on linked categories; warn when a linked product disappears. |
| `nextload:reconcile` | daily 03:30 | Compare supplier orders (last 2 days) with local rows; log unknown references and status mismatches. |

All commands are no-ops while `NEXTLOAD_ENABLED=false`.

## Admin pages (`/manager/next-load`)

- **Overview** — balance, linked categories, order counts, connection test, price list refresh.
- **Products & links** — supplier price list with local cost; `links/new` and `links/{category}` pages for linking, sourcing mode, **Restock** (buy N codes into stock, max 50 per click) and **Unlink**.
- **Supplier orders** — filters, details, **Refresh status**, **Release to stock** (unused codes of a completed order), **Reveal code** (recorded in the activity log as `next_load.pin_revealed`; refused if the audit write fails).

## Security

- Gift card codes (`cards.code`) and supplier pins/serials are encrypted with `APP_KEY`; `code_hash` / `pin_hash` (HMAC) keep them unique and searchable by exact value. **Rotating `APP_KEY` makes existing codes unreadable** — re-encrypt before rotating.
- Public category endpoints never return card rows, codes or cost.
- Request/response bodies are never logged (they contain pins); log lines carry endpoint, HTTP status and the supplier error message only.

## Staging test

Staging product `266` ("Test") costs `0.000`:

1. Link a category to product 266 with mode `supplier_only`.
2. Sell it from **Sell Gift Cards**, or buy it in the storefront; the code is delivered without any balance deduction.
3. Check **Supplier orders** for a `complete` row.

## Runbook

| Symptom | Action |
|---------|--------|
| Shop order stuck "awaiting code" | Supplier orders → filter `in_progress` / `pending_send` → **Refresh status**. POS retries `storefront:fulfill-digital` every 5 minutes. |
| `Stopped polling after N attempts` | Contact Next Load support with the reference and Next Load id; never place a new order for the same line by hand. |
| Completed shop order marked `unused` | The POS sale was cancelled or never retried. **Release to stock** makes the code sellable. |
| Low balance warnings | Top up with Next Load; supplier-backed categories disappear from listings when the balance does not cover the price. |
| `Next Load rejected the credentials` | Check the three `NEXTLOAD_*` credentials, recreate the containers. |
