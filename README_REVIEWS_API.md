# Reviews API

Public endpoint for submitting star reviews on games or gift-card categories. Reviews start as **pending** and appear in public product payloads only after an admin approves them.

No authentication is required.

## Endpoint

| Item | Value |
|------|--------|
| URL | `{BASE_URL}/api/reviews` |
| Method | `POST` |
| Auth | None |
| Rate limit | 30 requests per minute per IP |
| Content-Type | `application/json` |

Replace `{BASE_URL}` with the site origin, for example:

- Local WAMP: `http://localhost/gamessspot`
- Production: `https://gamesspoteg.com`

Full URL example: `http://localhost/gamessspot/api/reviews`

## Request

Send exactly one of `game_id` or `card_category_id`.

| Field | Type | Required | Notes |
|-------|------|----------|--------|
| `phone` | string, max 20 | Yes (or `phone_number`) | Customer phone; country-code tolerant |
| `phone_number` | string, max 20 | Yes (or `phone`) | Alias for `phone` |
| `stars` | integer 1–5 | Yes | Rating |
| `comment` | string, max 2000 | No | Optional review text |
| `game_id` | integer | Yes* | Existing game id |
| `card_category_id` | integer | Yes* | Existing gift-card category id |

\* Provide **exactly one** of `game_id` / `card_category_id`.

Phone matching follows the same rules as the device track API (with/without country code).

If no user exists for the phone, a customer account is created automatically.

### Behavior

- New reviews are stored with status `pending`.
- Resubmitting while `pending` or `rejected` overwrites stars/comment and resets to `pending`.
- An **approved** review cannot be silently replaced (HTTP 409).

### POST JSON

```http
POST /api/reviews HTTP/1.1
Host: localhost
Accept: application/json
Content-Type: application/json

{
  "phone": "+201226446623",
  "stars": 5,
  "comment": "Great game",
  "game_id": 12
}
```

Gift card example:

```json
{
  "phone": "+201226446623",
  "stars": 4,
  "comment": "Fast delivery",
  "card_category_id": 3
}
```

cURL:

```bash
curl -X POST "{BASE_URL}/api/reviews" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d "{\"phone\":\"+201226446623\",\"stars\":5,\"comment\":\"Great game\",\"game_id\":12}"
```

## Success response

**201 Created** (new review) or **200 OK** (updated pending/rejected review):

```json
{
  "success": true,
  "message": "Review submitted and pending approval.",
  "data": {
    "id": 1,
    "stars": 5,
    "comment": "Great game",
    "status": "pending",
    "reviewable_type": "Game",
    "reviewable_id": 12
  }
}
```

## Error responses

| Status | When |
|--------|------|
| 422 | Validation failed, or both product ids sent |
| 409 | An approved review already exists for this phone + product |
| 429 | Rate limit exceeded |

## Public read (approved only)

Approved reviews are embedded on product detail payloads (no phone numbers):

- `GET /api/games/{id}` → `data.reviews`
- `GET /api/card-categories/{id}` → `data.reviews`

Shape:

```json
{
  "average": 4.5,
  "count": 12,
  "items": [
    {
      "id": 1,
      "stars": 5,
      "comment": "Great game",
      "reviewer_name": "Customer",
      "created_at": "2026-09-20T10:00:00+00:00"
    }
  ]
}
```

## Admin

Managers with the `manage-reviews` permission can approve, reject, or delete reviews at `/manager/reviews`.
