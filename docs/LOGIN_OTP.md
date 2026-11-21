# Manager login OTP (email)

Staff sign-in at `/manager/login` uses a **6-digit email code** by default. Phone + password remains available at `/manager/login?method=password`.

## Flow

1. `POST /manager/login/otp/request` with `{ email }` — if the address matches an **active** user, a code is hashed onto `users.login_otp_*` and emailed synchronously (`Mail::sendNow`). Response never reveals whether the email exists.
2. `POST /manager/login/otp/verify` with `{ email, code, remember? }` — on success, starts the `admin` session (same role checks as password login) and clears the code.
3. After **5** wrong guesses for one issued code, the code is burned (`OneTimeCodeAttempts`).

## Config / env

| Env | Config | Default |
|-----|--------|---------|
| `AUTH_OTP_EXPIRE_MINUTES` | `auth_otp.expire_minutes` | `10` |
| `AUTH_OTP_ISSUE_PER_MINUTE` | `auth_otp.issue_per_minute` | `5` (per IP+email) |

Verify endpoint uses named limiter `auth-otp-verify` (12/min per IP).

## Mail

Uses the system ZeptoMail transport (`docs/ZOHO_CPAAS_MAIL.md`). Template: `resources/views/emails/admin-login-otp.blade.php`.

## Tests

```bash
php artisan test --filter=AdminLoginOtpTest
```
