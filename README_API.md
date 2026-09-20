# Games Spot API

Reference for HTTP API routes under `{BASE_URL}/api`.

Replace `{BASE_URL}` with your site origin, for example:

- Local: `http://localhost:8000` or `http://localhost/gamessspot`
- Production: `https://gamesspoteg.com`

All JSON endpoints expect `Accept: application/json`. Authenticated routes need:

```http
Authorization: Bearer {token}
```

Detailed docs for specific features:

- [Device Track API](README_DEVICE_TRACK_API.md)
- [Reviews API](README_REVIEWS_API.md)

---

## Route index

| Method | Path | Auth | Notes |
|--------|------|------|--------|
| `POST` | `/api/login` | No | Sanctum token |
| `GET` | `/api/user` | Sanctum | Current user |
| `GET` | `/api/games/platform/{4\|5}` | No | Catalog list (games or subscription) |
| `GET` | `/api/games/{id}` | No | Single catalog item + gallery + reviews |
| `GET` | `/api/card-ctegories/list` | No | Gift-card categories (typo preserved) |
| `GET` | `/api/card-categories/{id}` | No | Single gift-card category |
| `POST` | `/api/reviews` | No | Submit review (throttled 30/min) |
| `GET`/`POST` | `/api/device/track` | No | Device repairs by phone (throttled 30/min) |
| `POST` | `/api/customers` | Sanctum | Create/find customer |
| `GET` | `/api/orders/latest` | Sanctum | Latest orders by phone |
| `POST` | `/api/orders/receive` | Sanctum | Allocate game/subscription or gift card |
| `POST` | `/api/orders/check_stock` | Sanctum | Stock check for account products |
| `POST` | `/api/orders/check_card_stock` | Sanctum | Stock check for gift cards |
| `POST` | `/api/pos/receive-order` | Sanctum | POS webhook/receive |

There is **no** separate `/api/subscriptions` path. Subscriptions (PS Plus, etc.) use the **games** routes with `product_type=subscription`.

---

## Auth

### `POST /api/login`

**Request**

```json
{
  "phone": "+201226446623",
  "password": "secret"
}
```

**Success `200`**

```json
{
  "token": "1|xxxxxxxxxxxxxxxx"
}
```

**Errors:** `401` invalid credentials, `403` deactivated account.

### `GET /api/user`

Requires Bearer token. Returns the authenticated user model.

---

## Catalog: games and subscriptions

Catalog items live in `games` with `product_type`:

- `game` (default)
- `subscription` (PlayStation Plus, etc.)

Account sell types: `primary`, `secondary`, `full` (API). Manager UI also supports `offline`.

### `GET /api/games/platform/{platform}`

| Param | Values |
|-------|--------|
| `platform` (path) | `4` = PS4, `5` = PS5 |
| `product_type` (query) | `game` (default) or `subscription` |
| `page` (query) | Pagination page (20 per page) |

**Examples**

```http
GET /api/games/platform/5
GET /api/games/platform/5?product_type=subscription
GET /api/games/platform/4?product_type=game&page=2
```

**Success `200`** (Laravel paginator; items in `data`)

```json
{
  "current_page": 1,
  "data": [
    {
      "id": 12,
      "title": "God of War",
      "code": "GOW",
      "product_type": "game",
      "image_url": "assets/ps5/gow.webp",
      "types": {
        "primary": {
          "available": true,
          "stock": 3,
          "price": 250.0,
          "status": "enabled",
          "reason": null
        },
        "secondary": {
          "available": true,
          "stock": 2,
          "price": 180.0,
          "status": "enabled",
          "reason": null
        },
        "full": {
          "available": false,
          "stock": 0,
          "price": 400.0,
          "status": "disabled",
          "reason": "No full accounts available."
        }
      }
    }
  ],
  "per_page": 20,
  "total": 40
}
```

**Error `400`** invalid platform.

Subscriptions example item:

```json
{
  "id": 88,
  "title": "PlayStation Plus Extra",
  "product_type": "subscription",
  "types": { "primary": {}, "secondary": {}, "full": {} }
}
```

### `GET /api/games/{id}`

Works for both games and subscriptions (same table).

**Success `200`**

```json
{
  "success": true,
  "data": {
    "id": 12,
    "title": "God of War",
    "code": "GOW",
    "product_type": "game",
    "description": "<p>HTML description</p>",
    "full_price": 400.0,
    "ps4_image_url": "...",
    "ps5_image_url": "...",
    "ps4_primary_price": 200.0,
    "ps4_secondary_price": 150.0,
    "ps4_offline_price": 100.0,
    "ps5_primary_price": 250.0,
    "ps5_secondary_price": 180.0,
    "ps5_offline_price": 120.0,
    "ps4_primary_stock": 2,
    "ps4_secondary_stock": 1,
    "ps4_offline_stock": 0,
    "ps4_full_stock": 1,
    "ps5_primary_stock": 3,
    "ps5_secondary_stock": 2,
    "ps5_offline_stock": 1,
    "ps5_full_stock": 0,
    "gallery": [
      { "id": 1, "url": "https://.../assets/uploads/galleries/x.webp", "path": "assets/uploads/galleries/x.webp", "sort_order": 0 }
    ],
    "reviews": {
      "average": 4.5,
      "count": 12,
      "items": [
        {
          "id": 1,
          "stars": 5,
          "comment": "Great",
          "reviewer_name": "Customer",
          "created_at": "2026-09-20T10:00:00+00:00"
        }
      ]
    }
  }
}
```

Notes:

- Stock fields for offline/primary/secondary exclude full-account bundles; `ps*_full_stock` counts sellable full accounts.
- `reviews` includes **approved** reviews only (no phone numbers).

**Error `404`** game not found.

---

## Gift cards

### `GET /api/card-ctegories/list`

Legacy path spelling (`ctegories`). Categories that have at least one active code.

**Success `200`**

```json
{
  "status": true,
  "data": [
    {
      "id": 3,
      "name": "PlayStation Store 50$",
      "price": 50,
      "description": "<p>...</p>",
      "poster_image": "assets/...",
      "gallery": [],
      "reviews": { "average": 0, "count": 0, "items": [] },
      "cards": [ { "id": 1, "status": true } ]
    }
  ]
}
```

### `GET /api/card-categories/{id}`

**Success `200`**

```json
{
  "success": true,
  "data": {
    "id": 3,
    "name": "PlayStation Store 50$",
    "price": 50,
    "description": "<p>...</p>",
    "poster_image": "https://.../poster.webp",
    "gallery": [
      { "id": 2, "url": "https://...", "path": "...", "sort_order": 0 }
    ],
    "reviews": { "average": 4.0, "count": 2, "items": [] },
    "cards_count": 10
  }
}
```

---

## Reviews

### `POST /api/reviews`

Public, throttled **30/min**. Full request/response: [README_REVIEWS_API.md](README_REVIEWS_API.md).

Summary:

```json
{
  "phone": "+201226446623",
  "stars": 5,
  "comment": "Great",
  "game_id": 12
}
```

Or `"card_category_id": 3` instead of `game_id`. Status starts as `pending` until admin approval. Approved reviews appear on product payloads above.

---

## Device track

### `GET|POST /api/device/track`

Public, throttled **30/min**. Full docs: [README_DEVICE_TRACK_API.md](README_DEVICE_TRACK_API.md).

```json
{ "phone_number": "+201226446623" }
```

---

## Customers (Sanctum)

### `POST /api/customers`

**Request**

```json
{
  "name": "Ahmed",
  "email": "ahmed@example.com",
  "phone": "+201226446623",
  "password": "optional-min-8"
}
```

**Success `200`/`201`**

```json
{
  "success": true,
  "message": "Customer created successfully",
  "data": {
    "id": 10,
    "name": "Ahmed",
    "email": "ahmed@example.com",
    "phone": "+201226446623",
    "created_at": "..."
  }
}
```

If phone already exists, returns existing customer (`200`). Duplicate email for another user → `422`.

---

## Orders (Sanctum)

### `GET /api/orders/latest?buyer_phone=+201226446623`

**Success `200`**

```json
{
  "success": true,
  "orders": [ /* up to 10 latest Order models with seller, account.game, card */ ]
}
```

### `POST /api/orders/check_stock`

**Request**

```json
{
  "game_id": 12,
  "platform": "5",
  "type": "primary"
}
```

`type`: `primary` | `secondary` | `full`

**Success `200`**

```json
{
  "stock": 3,
  "is_available": true
}
```

For `full`, `stock` is the count of sellable full accounts (all three stocks &gt; 0).

### `POST /api/orders/check_card_stock`

**Request**

```json
{ "category_id": 3 }
```

**Success `200`**

```json
{
  "stock": 5,
  "is_available": true
}
```

### `POST /api/orders/receive`

Allocates stock and creates an order. Use **either** a game/subscription sale **or** a gift-card sale.

#### Game / subscription sale

```json
{
  "store_profile_id": 17,
  "game_id": 12,
  "buyer_phone": "+201226446623",
  "buyer_name": "Ahmed",
  "buyer_email": "ahmed@example.com",
  "price": 250,
  "type": "primary",
  "platform": "5",
  "wc_order_id": 12345
}
```

| Field | Notes |
|-------|--------|
| `type` | `primary`, `secondary`, or `full` |
| `platform` | `"4"` or `"5"` |
| `game_id` | Works for `product_type` game **or** subscription |
| `wc_order_id` | Required unless using storefront fields |
| `storefront_order_id` + `pos_transaction_id` | Optional storefront idempotency path |

**Success `201`**

```json
{
  "message": "Order created successfully!",
  "order_id": 99,
  "pos_order_id": null,
  "account_details": {
    "email": "psn@example.com",
    "password": "secret"
  }
}
```

**Error `422`** no matching account stock.

`full` decrements primary + secondary + offline together on an `is_full` account and records `sold_item` as `ps4_full` / `ps5_full`.

#### Gift-card sale

Send `card_category_id` (triggers card flow):

```json
{
  "card_category_id": 3,
  "store_profile_id": 17,
  "buyer_phone": "+201226446623",
  "buyer_name": "Ahmed",
  "price": 50,
  "buyer_email": "ahmed@example.com"
}
```

**Success** includes card `code` / `card_details`.

### `POST /api/pos/receive-order`

Authenticated POS receive endpoint (internal/POS integration). Prefer using the same Bearer token as other Sanctum routes.

---

## Quick cURL examples

Login:

```bash
curl -X POST "{BASE_URL}/api/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d "{\"phone\":\"+201226446623\",\"password\":\"secret\"}"
```

List PS5 subscriptions:

```bash
curl "{BASE_URL}/api/games/platform/5?product_type=subscription" \
  -H "Accept: application/json"
```

Game detail:

```bash
curl "{BASE_URL}/api/games/12" -H "Accept: application/json"
```

Receive primary sale:

```bash
curl -X POST "{BASE_URL}/api/orders/receive" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"store_profile_id\":17,\"game_id\":12,\"buyer_phone\":\"+201226446623\",\"buyer_name\":\"Ahmed\",\"buyer_email\":\"a@example.com\",\"price\":250,\"type\":\"primary\",\"platform\":\"5\",\"wc_order_id\":12345}"
```

---

## Common error shapes

| Status | Meaning |
|--------|---------|
| `400` | Bad platform / bad request |
| `401` | Missing/invalid token or login failure |
| `403` | Deactivated user |
| `404` | Resource not found |
| `409` | Conflict (e.g. approved review already exists) |
| `422` | Validation or no stock |
| `429` | Rate limit (reviews / device track) |
