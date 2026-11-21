<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Manager login OTP (sent synchronously via sendNow).
 */
class AdminLoginOtpMail extends Mailable
{
    use SerializesModels;

    public function __construct(public User $user, public string $code)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your login code',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-login-otp',
            with: [
                'user' => $this->user,
                'code' => $this->code,
                'expireMinutes' => max(1, (int) config('auth_otp.expire_minutes', 10)),
            ],
        );
    }
}
