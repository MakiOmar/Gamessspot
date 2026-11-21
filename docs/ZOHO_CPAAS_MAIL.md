# Zoho CPaaS transactional mail (Accounts)

Accounts (`gamessspot`) sends transactional mail through **Zoho CPaaS** / ZeptoMail using the same Agent and verified domain as POS.

**Source of truth:** POS repo `docs/integrations/ZOHO_CPAAS_MAIL.md` (setup, regions, checklist, deploy). This file only covers Accounts-specific notes.

## Accounts specifics

| Item | Value |
|------|--------|
| Package | `zohomail/laravel-zeptomail:dev-main` |
| Mailer | `zeptomail` in `config/mail.php` |
| Credentials | `config/services.php` → `zeptomail.host` / `zeptomail.token` from `ZEPTOMAIL_*` |
| Per-store SMTP | **None** — single system `.env` only |
| App mail | Device repair notices via `DeviceServiceNotification` / `DeviceServiceMail`; manager login OTP via `AdminLoginOtpMail` ([LOGIN_OTP.md](./LOGIN_OTP.md)) |

## `.env` (production / staging)

```env
MAIL_MAILER=zeptomail
ZEPTOMAIL_HOST=zoho.com
ZEPTOMAIL_TOKEN="Zoho-enczapikey YOUR_SEND_API_KEY"
MAIL_FROM_ADDRESS=noreply@gamesspoteg.com
MAIL_FROM_NAME="${APP_NAME}"
```

Local Mailpit remains Option B in `.env.example` (`MAIL_MAILER=smtp`, `MAIL_HOST=mailpit`).

## Smoke test

```bash
php artisan tinker
```

```php
Mail::raw('Accounts ZeptoMail OK', function ($message) {
    $message->to('you@example.com')->subject('Testing Accounts mail');
});
```

Or change a device-service status for a user with a real email and confirm delivery.

After editing `accounts.env` on the VPS, recreate Accounts containers and clear config cache if needed.
