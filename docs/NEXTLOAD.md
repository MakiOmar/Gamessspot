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
   | `NEXTLOAD_DIAGNOSTICS_KEY` | — | Secret for the login-free [diagnostics page](#diagnostics-page-no-login). Empty disables it. |

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

### What linking does

Linking a category (**Products & links → link**) stores the Next Load `product_id`, the mode, the supplier price (`supplier_cost`) and the product name. The hourly `nextload:sync-products` keeps price and name current. Linking alone buys nothing; codes are bought only when a sale (or **Restock**) needs one. **Unlink** sets the mode back to `local_only` and keeps existing codes and supplier orders.

### Sale flow (`CardSourcingService`)

Every sale (POS, storefront, manager sell screen) calls `POST /api/orders/receive`; the category mode decides where the code comes from:

1. **Local code** (`local_only`, `local_first`) — the oldest active code in the category is locked, marked sold and returned. Nothing is sent to Next Load.
2. **No local code left** — `local_only` stops with *"No active code found"*. `local_first` and `supplier_only` continue to Next Load (only if `NEXTLOAD_ENABLED=true` and credentials are set; otherwise the same error).
3. **Checks before buying** — the linked product must still be in the price list, and its price converted with `NEXTLOAD_EXCHANGE_RATE` must not exceed the category sale price (unless `NEXTLOAD_ALLOW_LOSS=true`). Failing either returns 422 and nothing is charged.
4. **Buy one code** — a `next_load_orders` row is saved, then `CreateOrder` is sent (see [Purchase flow](#purchase-flow-and-guarantees)):
   - `Complete` → the pin becomes an encrypted `cards` row (`source = next_load`, `cost` = converted supplier price), the order is created and the code is returned like a local one.
   - `InProgress` → 202 `pending`. Shop/POS: the retry for the same line returns the code. Manager sale: the code is added to stock when delivered.
   - `Failed` → 422, not charged; a later retry uses a new reference.

### Availability shown to sellers and the shop

`available_count` (listings) and the stock check use local active codes first. When there are none (or the mode is `supplier_only`), the count is how many codes the cached balance can buy at the current supplier price, capped at 99. It is `0` — and the category is hidden from listings — when Next Load is disabled, the product left the price list, the balance is too low, or the loss guard blocks the price.

### Choosing a mode

- **`local_first`** — recommended for most cards: bulk-bought codes sell first, Next Load covers stock-outs.
- **`supplier_only`** — no stock to manage; every sale costs the live supplier price and depends on Next Load being reachable.
- **`local_only`** — pauses supplier buying (e.g. price problem or supplier outage) while keeping the link and price sync. **Restock** is disabled in this mode.

To pre-buy stock, use **Restock** in `local_first` (codes go to local stock and sell before any new supplier purchase).

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

## Card artwork

Next Load products have no images. Categories **without a poster** get generated HTML artwork (white "GIFT CARD" band with face value and flag, brand-coloured area with logo):

- **Source text:** `card_categories.supplier_product_name` (the Next Load name, saved when linking and refreshed by `nextload:sync-products`), else the category name — so local categories named like "PSN US 10$" get artwork too.
- **Parsing** (`App\Services\CardArt\CardArtParser`): brand keywords (PlayStation/PSN, Xbox, Amazon, iTunes/Apple, Google Play, Steam, Nintendo; anything else is `generic` with its name as label), country tokens (`US`, `UK`, `KSA`, `UAE`, …; upper-case 2-letter codes only), and a value next to a currency symbol or code (`$10`, `10$`, `10 £`, `100 SAR`). No country token → country from currency; "Global" → globe icon. No value → no artwork.
- **Overrides:** the link form (`links/{category}`) has a **Card artwork** section (brand, country flag, face value) with a live preview (`GET /manager/next-load/links/art-preview`). Leave on *Automatic* unless the preview is wrong.
- **Output:** `card_art` on the public category APIs ([README_API.md](../README_API.md)), the `<x-card-art>` Blade component (sell screen, categories list) and the storefront `GiftCardArt` component.
- **Assets:** `public/images/card-art/flags` (flag-icons, MIT) and `brands` (Simple Icons, CC0) — see `public/images/card-art/LICENSES.md`. Add a country/brand by dropping the SVG and adding it to `App\Services\CardArt\CardArtCatalog`.

## Security

- Gift card codes (`cards.code`) and supplier pins/serials are encrypted with `APP_KEY`; `code_hash` / `pin_hash` (HMAC) keep them unique and searchable by exact value. **Rotating `APP_KEY` makes existing codes unreadable** — re-encrypt before rotating.
- Public category endpoints never return card rows, codes or cost.
- Request/response bodies are never logged (they contain pins); log lines carry endpoint, HTTP status and the supplier error message only.

## Diagnostics page (no login)

`GET /nextload/diagnostics?key={NEXTLOAD_DIAGNOSTICS_KEY}` runs read-only checks with the real client and lists every HTTP exchange (time sent, duration, URL, headers, body, status, response):

1. Outbound IP of the server (what Next Load must allowlist; a wrong IP shows as `Not Allow Ip`).
2. Fresh login (`Token`) + `Balance`.
3. `ProductsList` (first 15 rows shown) and whether staging test product `266` is listed.

It never calls `CreateOrder`. Passwords, the security code, tokens, pins, serials and codes are masked; the email is partly masked. Unset `NEXTLOAD_DIAGNOSTICS_KEY` (default) or a wrong key returns 404; `throttle:6,1`, `noindex`, `no-store`. Use a long random key and clear it after support sessions, since anyone with the URL sees the balance and price list.

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
