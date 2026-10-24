# Next Load gift card supplier

Accounts can buy gift card codes from the [Next Load](https://stg.next-load.com) merchant API at sale time. POS, the storefront and the manager sell screen keep calling `POST /api/orders/receive` exactly as before; Accounts decides per card category whether the code comes from local stock or from Next Load.

Gift cards only. Subscriptions (PS Plus, etc.) stay shared game accounts.

The **staff guide** below needs no technical knowledge. Technical details start at [Setup](#setup).

## Staff guide (plain language)

### The two places a code can come from

Think of it like a shop:

- **Your drawer** = codes already saved in the system for that card. We call these *local codes*. They get there in two ways only:
  - **By hand:** **Gift Cards → Gift Cards List/Add** (same as before Next Load existed).
  - **With Restock:** you press a button and choose how many codes to buy from Next Load now (see [Restock](#restock--buying-codes-in-advance)).
- **Next Load** = a wholesaler. You put money in your Next Load account (your **balance**). When the system buys a code from them, the price is taken from that balance and the code arrives in seconds.

Each gift card category (for example *"Amazon KSA 100 SAR"*) has a **mode** that decides: drawer, wholesaler, or both.

Where to find everything: **Manager → Gift Cards → Next Load Supplier**.

### The three modes at a glance

| | Local only | Local first (recommended) | Next Load only |
|---|---|---|---|
| Where a sale's code comes from | Drawer only | Drawer first, then Next Load | Next Load only |
| Do I have to add codes? | **Yes**, by hand | **No** (optional) | **No** |
| Buys from Next Load by itself? | Never | Only when the drawer is empty, **1 code per sale** | Every sale, **1 code per sale** |
| Restock button | Off | On | On (but don't use it — see below) |
| Codes in the drawer | Sold | Sold first | **Ignored** (not sold) |

### What "linking" a category means

Linking tells the system: *"This card of ours is the same as that product at Next Load."*

How to link:

1. Open **Products & links**, find the product (e.g. *amazon KSA 100 SAR*) and click **Link / create category**.
2. Choose an **existing category** of yours, or **create a new one** (type a name and the price you sell it for).
3. Choose a **mode** (explained below). If unsure, pick **Local first**.
4. Optional: a **daily limit** and the **card artwork**.
5. Click **Save**.

What linking does **not** do:

- It does **not** buy anything and does **not** spend money. Money is only spent when a customer actually buys a card (or when you press **Restock**).
- It does **not** add codes. An existing category keeps the codes it already had; a newly created category starts **empty**.
- It does **not** change the price you sell at. You still decide your sale price.

What happens after linking:

- Every hour the system checks Next Load's price for that product.
- If Next Load's price becomes **higher than your sale price**, the system **will not buy** (so you never sell at a loss). Raise your sale price to start selling again.
- **Unlink** turns the category back to *Local only*. Codes already in your drawer stay there.

### The three modes — with an example

Example for all three: you sell **Amazon KSA 100 SAR** cards. Your drawer has **5 codes**. A customer buys one card.

#### 1. Local only — "only sell what's in my drawer"

| Situation | What happens |
|-----------|--------------|
| Drawer has 5 codes | The customer gets a code from the drawer. Drawer now has 4. Next Load is never used. |
| Drawer is empty | The card **disappears from the shop** and the sell screen says *"No active code found"*. Nothing is bought. |

- **Codes:** you must add them **by hand**. The Restock button is off in this mode.
- **Use it when:** you only want to sell codes you already have, or you want to **pause** buying from Next Load (for example, the price went up).

#### 2. Local first — "sell from my drawer, then from Next Load" (recommended)

| Situation | What happens |
|-----------|--------------|
| Drawer has 5 codes | Sales 1 to 5 use the drawer. No money is spent at Next Load. |
| Sale number 6 (drawer empty) | The system **automatically buys 1 code** from Next Load and gives it to the customer. The price is taken from your balance. |
| Drawer empty **and** balance too low | The card disappears from the shop until you top up. |

- **Codes:** adding codes is **optional**. If the drawer is empty from day one, **every** sale buys from Next Load (just like *Next Load only*) until you add some. Fill the drawer by hand, or with **Restock**.
- **Use it when:** you want to sell codes you already have first and **never run out**. Best choice for most cards.

#### 3. Next Load only — "always buy a fresh code from Next Load"

| Situation | What happens |
|-----------|--------------|
| Any sale | The system buys **1 code from Next Load** for that customer, every time. |
| Your drawer | Is **ignored**. The 5 codes stay there unsold (switch to *Local first* to sell them). |
| Balance too low | The card disappears from the shop until you top up. |

- **Codes:** you don't add any. Don't use **Restock** here either: restocked codes go to the drawer, and this mode never sells from the drawer.
- **Use it when:** you don't want to manage any stock at all.

### Restock — buying codes in advance

Restock is a button on the card's link page (**Next Load Supplier → Products & links → open the linked card**). It buys codes **now** and puts them in your drawer, ready to sell.

**It never runs by itself.** Nothing refills your stock automatically. You decide when and how many.

Step by step:

1. Click **Restock from Next Load**.
2. A box asks **how many codes** to buy. It starts at **1**; you can enter **1 to 50**.
3. Click **Buy codes** (or **Cancel** — nothing is spent).
4. Your balance goes down by *number of codes × Next Load price*. Example: 10 codes × 26.5 = **265** taken from the balance.
5. You will see one of these:
   - *"10 code(s) added to stock"* — done, the codes are in the drawer.
   - *"Next Load is processing the order"* — the codes are added to the drawer by themselves when they arrive. No need to click again.
   - *"Next Load could not fulfil the order (not charged)"* — nothing was taken. Check the balance and try again.

Good to know:

- Only works in **Local first** or **Next Load only** (and is only useful in **Local first**).
- It won't buy if Next Load's price is **above your sale price**.
- The **daily limit does not apply** to Restock: you chose the amount yourself.
- Need more than 50? Click Restock again.

### What the customer sees

- **Normal case:** the customer gets the code straight away, whether it came from the drawer or from Next Load. They can't tell the difference.
- **Next Load is slow ("still processing"):**
  - *Online shop / POS:* the system keeps trying by itself. The customer gets the code once it arrives (by email for online orders).
  - *Manager sell screen:* you see *"Next Load is still processing"*. The code is put **in your drawer** when it arrives. Sell it again from the drawer.
- **Next Load refuses the order:** **no money is taken**. Try again, or check the balance.

### The balance is shared — read this

Your Next Load balance is **one wallet for all cards**.

Example: your balance can buy **100 Amazon cards** *or* **150 PlayStation cards**.

- The shop may show **99 available** for Amazon **and** 99 for PlayStation. That does **not** mean you have 198 cards. Both numbers come from the same money.
- If you sell 50 Amazon cards, half the money is gone. PlayStation can now only buy about 75.

To control it:

- **Daily limit** (on the link page): for example *"Amazon: max 30 per day"*. After 30, Amazon stops buying from Next Load until tomorrow, so money is left for other cards. Empty = no limit.
- **Restock** (mode *Local first*): buy, for example, 20 PlayStation codes **now**. That money is now "reserved" for PlayStation: the codes are in your drawer and sell first. See [Restock](#restock--buying-codes-in-advance).
- **Top up** before the money runs out. The admin can set a low-balance warning. After topping up, press **Test connection** on the overview page so the shop updates right away.

### Daily checklist

1. Open **Next Load Supplier → Overview**.
2. Check the **Balance**. If it is red (low), top up at Next Load.
3. In **Linked categories**, check **Can buy now**. A red **0** means that card can't be bought from Next Load right now. The reason is low balance, the daily limit being used up, or Next Load's price being above your sale price.
4. Check **Supplier orders**. Orders marked **failed** cost nothing. Orders that are stuck **in progress** for a long time should be reported to the admin.

### Quick answers

| Question | Answer |
|----------|--------|
| I linked a card but nothing was bought. Is that broken? | No. Codes are only bought when someone buys the card (or you press Restock). |
| In *Local first*, do I have to add codes myself? | No. With an empty drawer, each sale buys one code from Next Load. Add codes (by hand or Restock) only if you want to sell from stock first. |
| Does Restock keep my stock full automatically? | No. It buys only when you click it, and only the number you type (1–50). |
| How do I add codes by hand? | **Gift Cards → Gift Cards List/Add**, same as before. |
| A card disappeared from the shop. | Drawer is empty and Next Load can't buy: balance is low, daily limit reached, mode is *Local only*, or Next Load's price is above your sale price. |
| Which mode should I use? | **Local first**, unless you have a reason not to. |
| I want to stop buying from Next Load for one card. | Switch that card to **Local only**. It still sells what's in the drawer. |
| A customer was charged but got no code. | Tell the admin with the order number. The system retries by itself first; the admin can finish it manually. |

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

Linking a category (**Products & links → link**) stores the Next Load `product_id`, the mode, the optional daily limit, the supplier price (`supplier_cost`) and the product name. The hourly `nextload:sync-products` keeps price and name current. Linking alone buys nothing; codes are bought only when a sale (or **Restock**) needs one. **Unlink** sets the mode back to `local_only` and keeps existing codes and supplier orders.

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

`available_count` (listings) and the stock check use local active codes first. When there are none (or the mode is `supplier_only`), the count is how many codes the cached balance can buy at the current supplier price, capped at 99 and at what is left of the category's daily limit. It is `0` — and the category is hidden from listings — when Next Load is disabled, the product left the price list, the balance is too low, the daily limit is used up, or the loss guard blocks the price.

### Managing the shared balance

Next Load sells against **one money balance**, not per-product stock. Each category's count is calculated from that same balance, so the counts are **not** additive: with a balance that buys 100 Amazon *or* 150 PlayStation codes, both may show 99, and selling 50 Amazon codes leaves room for about 75 PlayStation codes.

- **Balance cache:** read from Next Load every 15 minutes (`nextload:balance`), **lowered immediately** after each charged order, and dropped after a rejected order so the next check asks Next Load. Concurrent sales may make the cached value drift slightly until the next refresh.
- **Daily limit** (link page, optional): max codes Next Load may buy per day for **sales** of that category (failed orders, local codes and Restock are not counted). When it is reached, supplier sales of that category return 422 and the category's supplier count is 0 until midnight (app timezone). Use it so one popular product cannot use the whole balance.
- **Reserving stock:** **Restock** in `local_first` turns balance into real codes for that category now; they sell first and are unaffected by the balance or a supplier outage.
- **Low balance:** set `NEXTLOAD_LOW_BALANCE` to about one busy day of supplier purchases (e.g. 20 × the most expensive linked product) and top up when the warning appears. Running dry makes shop orders that were already paid fail allocation (retried, then manual).
- **Overview page:** the **Linked categories** table shows per product the local codes, purchases today (vs. limit) and what the shared balance can buy now. **Test connection** re-reads the balance after a top-up.

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
| `nextload:balance` | every 15 min | Re-read and cache the real balance (corrects the per-purchase deductions); low-balance warning. |
| `nextload:sync-products` | hourly | Refresh `supplier_cost` on linked categories; warn when a linked product disappears. |
| `nextload:reconcile` | daily 03:30 | Compare supplier orders (last 2 days) with local rows; log unknown references and status mismatches. |

All commands are no-ops while `NEXTLOAD_ENABLED=false`.

## Admin pages (`/manager/next-load`)

- **Overview** — shared balance, linked categories table (local codes, bought today / limit, can buy now), order counts, connection test, price list refresh.
- **Products & links** — supplier price list with local cost; `links/new` and `links/{category}` pages for linking, sourcing mode, daily limit, card artwork, **Restock** (buy N codes into stock, max 50 per click) and **Unlink**.
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
