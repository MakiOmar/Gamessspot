# Device Track API

Public endpoint for looking up device service records by customer phone number. It matches the `/device/track` website search and returns **all statuses**, including `delivered`.

No authentication is required.

## Endpoint

| Item | Value |
|------|--------|
| URL | `{BASE_URL}/api/device/track` |
| Methods | `POST` (recommended), `GET` |
| Auth | None |
| Rate limit | 30 requests per minute per IP |
| Content-Type | `application/json` (POST) |

Replace `{BASE_URL}` with the site origin, for example:

- Local WAMP: `http://localhost/gamessspot`
- Production: `https://gamesspoteg.com`

Full URL example: `http://localhost/gamessspot/api/device/track`

## Request

Send the customer phone with or without the country code. These all match the same stored number (`+201226446623`):

- `+201226446623`
- `201226446623`
- `1226446623`
- `01226446623`

If no country code is present, Egypt (`+20`) is assumed.

Accepted fields (use one):

| Field | Type | Required | Notes |
|-------|------|----------|--------|
| `phone_number` | string, max 20 | Yes (unless `phone` is sent) | Preferred field name |
| `phone` | string, max 20 | Yes (unless `phone_number` is sent) | Alias for `phone_number` |

### POST JSON (recommended)

```http
POST /api/device/track HTTP/1.1
Host: localhost
Accept: application/json
Content-Type: application/json

{
  "phone_number": "+201226446623"
}
```

cURL:

```bash
curl -X POST "{BASE_URL}/api/device/track" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d "{\"phone_number\":\"+201226446623\"}"
```

JavaScript:

```javascript
const response = await fetch(`${BASE_URL}/api/device/track`, {
  method: 'POST',
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({ phone_number: '+201226446623' }),
});

const payload = await response.json();
```

### POST form body

```bash
curl -X POST "{BASE_URL}/api/device/track" \
  -H "Accept: application/json" \
  -d "phone_number=+201226446623"
```

### GET query string

```http
GET /api/device/track?phone_number=%2B201226446623 HTTP/1.1
Accept: application/json
```

```bash
curl -G "{BASE_URL}/api/device/track" \
  -H "Accept: application/json" \
  --data-urlencode "phone_number=+201226446623"
```

Stored phones are matched in both directions: a request with `+20...` finds a national-only record, and a request with only the local number finds a `+20...` record.

## Success response (`200`)

At least one service record exists for that phone. Records are newest first.

```json
{
  "success": true,
  "message": "Services found.",
  "count": 2,
  "data": [
    {
      "id": 1842,
      "tracking_code": "DR272ACE37",
      "status": "delivered",
      "status_display": "Delivered",
      "device_serial_number": "0000",
      "notes": "HDMI port repair",
      "submitted_at": "2026-09-05T12:24:00+03:00",
      "status_updated_at": "2026-09-05T12:24:00+03:00",
      "client_name": "Amr Emad",
      "phone": "+201226446623",
      "device_model": {
        "id": 12,
        "name": "PS5 Slim Digital",
        "brand": "Sony",
        "full_name": "Sony PS5 Slim Digital"
      },
      "store_profile": {
        "id": 3,
        "name": "ELSHEKH ZAYED"
      }
    },
    {
      "id": 1830,
      "tracking_code": "DR671B3002",
      "status": "received",
      "status_display": "Received",
      "device_serial_number": "8563",
      "notes": null,
      "submitted_at": "2026-09-05T11:54:00+03:00",
      "status_updated_at": "2026-09-05T11:54:00+03:00",
      "client_name": "Amr Emad",
      "phone": "+201226446623",
      "device_model": {
        "id": 8,
        "name": "PS4 Fat",
        "brand": "Sony",
        "full_name": "Sony PS4 Fat"
      },
      "store_profile": {
        "id": 1,
        "name": "CITY STARS"
      }
    }
  ]
}
```

### `data[]` fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Internal repair ID |
| `tracking_code` | string | Public tracking code (for example `DR272ACE37`) |
| `status` | string | `received`, `processing`, `ready`, or `delivered` |
| `status_display` | string | Label for UI (`Received`, `Processing`, `Ready for Pickup`, `Delivered`) |
| `device_serial_number` | string | Device serial |
| `notes` | string \| null | Staff/customer notes |
| `submitted_at` | string \| null | ISO-8601 datetime |
| `status_updated_at` | string \| null | ISO-8601 datetime |
| `client_name` | string | Customer name |
| `phone` | string | Stored customer phone |
| `device_model` | object \| null | Model details |
| `store_profile` | object \| null | Store that received the device |

## Empty result (`404`)

No service records for that phone.

```json
{
  "success": false,
  "message": "No services found for this phone number.",
  "count": 0,
  "data": []
}
```

## Validation error (`422`)

Missing or invalid phone.

```json
{
  "message": "The phone number field is required.",
  "errors": {
    "phone_number": [
      "The phone number field is required."
    ]
  }
}
```

## Rate limit (`429`)

More than 30 requests in one minute from the same IP. Wait and retry.

## Client checklist

1. Call `POST {BASE_URL}/api/device/track` with JSON `{ "phone_number": "+20..." }`.
2. Send `Accept: application/json`.
3. Treat `200` + `success: true` as hits. Render `data` using `status_display` and `tracking_code`.
4. Treat `404` as “no records for this phone” (show an empty state, not a crash).
5. Treat `422` as a form validation error on the phone field.
6. Do not require a login token. This endpoint is public, same as `/device/track`.
