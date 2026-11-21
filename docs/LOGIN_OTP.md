# Manager login OTP (email)

Staff sign-in at `/manager/login` uses a **6-digit email code** only. Phone + password login is removed.

## Flow

1. `POST /manager/login/otp/request` with `{ email }` — if the address matches an **active staff** user (any role except `customer`), a code is hashed onto `users.login_otp_*` and emailed synchronously (`Mail::sendNow`). Customer accounts and unknown addresses get the same response and no email. A customer session that opens `/manager` is signed out and returned to the login form.
2. `POST /manager/login/otp/verify` with `{ email, code, remember? }` — on success, starts the `admin` session (role checks) and clears the code.
3. After **5** wrong guesses for one issued code, the code is burned (`OneTimeCodeAttempts`).

## Config / env

| Env | Config | Default |
|-----|--------|---------|
| `AUTH_OTP_EXPIRE_MINUTES` | `auth_otp.expire_minutes` | `10` |
| `AUTH_OTP_ISSUE_PER_MINUTE` | `auth_otp.issue_per_minute` | `5` (per IP+email) |

Verify endpoint uses named limiter `auth-otp-verify` (12/min per IP).

## Mail

Uses the system ZeptoMail transport (`docs/ZOHO_CPAAS_MAIL.md`). Template: `resources/views/emails/admin-login-otp.blade.php`.

## Machine API login (separate from OTP)

POS and other integrations still use `POST /api/login` (phone + password). That route is **allowlisted**:

| Env | Purpose |
|-----|---------|
| `API_LOGIN_USER_IDS` | Comma-separated user ids allowed to mint tokens |
| `API_LOGIN_PHONES` | Optional phone allowlist (exact `users.phone` match) |
| `API_TOKEN_TTL_DAYS` | Token lifetime (default `8`) |

Tokens are issued with scoped abilities (`orders:receive`, `pos:receive-order`, `customers:write`, `orders:check-stock`) — not `*`. Protect those routes with Sanctum `abilities:…`. Set the allowlist to the same account POS uses via `ACCOUNTS_AUTH_PHONE` / `ACCOUNTS_AUTH_PASSWORD`.

## Tests

```bash
php artisan test --filter=AdminLoginOtpTest
php artisan test --filter=ApiRoutesTest
```
