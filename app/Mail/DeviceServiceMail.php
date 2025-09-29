<?php

namespace App\Mail;

use App\Models\DeviceRepair;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DeviceServiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public $deviceRepair;
    public $type;
    public $logoUrl;
    public $siteUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(DeviceRepair $deviceRepair, string $type = 'created')
    {
        $this->deviceRepair = $deviceRepair;
        $this->type = $type;
        $this->siteUrl = config('app.url');
        $this->logoUrl = $this->siteUrl . '/assets/img/finallogo.png';
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->type === 'created' 
            ? 'Device Service Request Received - ' . $this->deviceRepair->tracking_code
            : 'Device Service Status Update - ' . $this->deviceRepair->tracking_code;

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.device-service',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
