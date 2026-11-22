# Manager login OTP (email)

Staff sign-in at `/manager/login` uses a **6-digit email code** only. Phone + password login is removed.

## Flow

1. `POST /manager/login/otp/request` with `{ email }` — if the address matches an **active staff** user (any role except `customer`), a code is hashed onto `users.login_otp_*` and emailed synchronously (`Mail::sendNow`). Customer accounts and unknown addresses get the same generic response and no email. A customer session that opens `/manager` is signed out and returned to the login form.
2. `POST /manager/login/otp/verify` with `{ email, code }` — on success, starts the `admin` session (role checks) and clears the code. No “remember me”; the session lasts until logout or the normal session lifetime.
3. After **5** wrong guesses for one issued code, the code is burned (`OneTimeCodeAttempts`).
4. **Resend cooldown** (default **60s**): another request within the window sends nothing and shows a wait message. After the cooldown, a still-valid code is **re-mailed unchanged** (plaintext kept in cache until expiry) so older inbox copies keep working; only an expired/missing code mints a new one. The login UI disables Resend with a countdown and reminds staff to check spam. Unknown emails stay non-enumerating (generic “if registered” copy; no cooldown state).

## Config / env

| Env | Config | Default |
|-----|--------|---------|
| `AUTH_OTP_EXPIRE_MINUTES` | `auth_otp.expire_minutes` | `10` |
| `AUTH_OTP_ISSUE_PER_MINUTE` | `auth_otp.issue_per_minute` | `5` (per IP+email) |
| `AUTH_OTP_RESEND_COOLDOWN_SECONDS` | `auth_otp.resend_cooldown_seconds` | `60` |
| `ADMIN_OTP_WHITELIST` | `auth_otp.whitelist` | empty (all active staff with manager access) |
| `ADMIN_EMAIL_LOCK` | `auth_otp.email_lock` | `true` (staff/admin emails cannot be changed in manager UI) |

**Whitelist:** comma-separated `user_id:email` pairs (emails compared case-insensitively). When **non-empty**, only those exact id+email matches may receive or verify a manager OTP — so if user `32` is listed as `32:mhmuddmostfa0@gmail.com` and their DB email was changed, no code is sent. Unknown / non-listed staff get the same silent response as an unknown address. Blocks are logged (`Admin login OTP: whitelist_block`). Recreate the Accounts container after changing `.env` so `config:cache` picks it up.

### Diagnosing “OTP not received”

Every request/send/verify writes structured lines to the Laravel log (message prefix `Admin login OTP:`). The plaintext code is never logged. Public responses stay non-enumerating; logs still record the real reason.

| Message | Meaning |
|---------|---------|
| `Admin login OTP: request` | Early exit before mail (`reason`: `ineligible`, `whitelist`, `cooldown`) |
| `Admin login OTP: whitelist_block` | Whitelist rejected the id/email pair |
| `Admin login OTP: send_attempt` | About to call the mailer (`mailer`, `mail_from`, `reused`) |
| `Admin login OTP: send_ok` | Transport accepted the message (`duration_ms`) — inbox delivery is still up to the provider/spam filters |
| `Admin login OTP: send_failed` | Transport/API threw (`exception`, `error`); UI still looks like a silent skip |
| `Admin login OTP: verify` | Code check result (`reason`: `accepted`, `bad_code`, `missing_or_expired`, …) |

On the VPS (Accounts container):

```bash
docker compose exec -T accounts sh -lc \
  "grep -F 'Admin login OTP' \$(ls -1t /var/www/html/storage/logs/laravel*.log | head -1) | tail -n 80"
```

Filter by address with `| grep -F 'user@example.com'`. If you see `send_ok` but the staff still has nothing, check spam and Zoho CPaaS delivery logs — the app already handed the message off.

**Email lock:** when `ADMIN_EMAIL_LOCK=true` (default), manager user edit cannot change email on any staff/admin account (roles other than `customer`). The field is read-only in the UI; a forged change returns validation error. Customer emails remain editable. The customers API already refused staff email rebinds; device-repair intake also skips rewriting staff emails when the lock is on. Set `ADMIN_EMAIL_LOCK=false` only for controlled recovery.

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

`ACCOUNTS_AUTH_PASSWORD` on POS must be a long random secret. Accounts keeps only the **bcrypt hash** on `users.password`; rotate by hashing a new secret on the allowlisted user and updating POS `.env`, then recreate the POS container and clear `accounts_auth_token` cache.

## Tests

```bash
php artisan test --filter=AdminLoginOtpTest
php artisan test --filter=ApiRoutesTest
```
