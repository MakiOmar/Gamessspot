<?php

namespace App\Notifications;

use App\Models\DeviceRepair;
use App\Mail\DeviceServiceMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DeviceServiceNotification extends Notification
{
    use Queueable;

    protected $deviceRepair;
    protected $type;

    /**
     * Create a new notification instance.
     */
    public function __construct(DeviceRepair $deviceRepair, string $type = 'created')
    {
        $this->deviceRepair = $deviceRepair;
        $this->type = $type;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Log the notifiable user details
        Log::info('DeviceServiceNotification via() called', [
            'user_id' => $notifiable->id ?? 'null',
            'user_email' => $notifiable->email ?? 'null',
            'user_name' => $notifiable->name ?? 'null',
            'device_repair_id' => $this->deviceRepair->id,
            'tracking_code' => $this->deviceRepair->tracking_code,
            'notification_type' => $this->type
        ]);
        
        // Check if email exists
        $emailExists = !empty($notifiable->email);
        Log::info('Email existence check', [
            'email' => $notifiable->email ?? 'null',
            'exists' => $emailExists,
            'is_empty' => empty($notifiable->email)
        ]);
        
        // Validate email format
        $emailValid = false;
        if ($emailExists) {
            $emailValid = filter_var($notifiable->email, FILTER_VALIDATE_EMAIL);
            Log::info('Email validation check', [
                'email' => $notifiable->email,
                'filter_var_result' => $emailValid !== false ? 'valid' : 'invalid',
                'validation_result' => $emailValid
            ]);
        }
        
        // Determine channels
        $channels = [];
        if ($emailExists && $emailValid !== false) {
            $channels = ['mail'];
            Log::info('Notification will be sent via mail', [
                'channels' => $channels,
                'email' => $notifiable->email
            ]);
        } else {
            Log::warning('Notification skipped - invalid or missing email', [
                'email' => $notifiable->email ?? 'null',
                'email_exists' => $emailExists,
                'email_valid' => $emailValid !== false,
                'channels' => []
            ]);
        }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable)
    {
        Log::info('DeviceServiceNotification toMail() called', [
            'user_id' => $notifiable->id,
            'user_email' => $notifiable->email,
            'user_name' => $notifiable->name,
            'device_repair_id' => $this->deviceRepair->id,
            'tracking_code' => $this->deviceRepair->tracking_code,
            'notification_type' => $this->type
        ]);
        
        return new DeviceServiceMail($this->deviceRepair, $this->type);
    }

    /**
     * Get the email subject based on type
     */
    private function getSubject(): string
    {
        switch ($this->type) {
            case 'created':
                return 'Device Service Request Received - ' . $this->deviceRepair->tracking_code;
            case 'status_changed':
                return 'Device Service Status Update - ' . $this->deviceRepair->tracking_code;
            default:
                return 'Device Service Notification - ' . $this->deviceRepair->tracking_code;
        }
    }

    /**
     * Get the greeting based on type
     */
    private function getGreeting(): string
    {
        switch ($this->type) {
            case 'created':
                return 'Hello ' . $this->deviceRepair->user->name . '!';
            case 'status_changed':
                return 'Hello ' . $this->deviceRepair->user->name . '!';
            default:
                return 'Hello!';
        }
    }

    /**
     * Get the main message based on type
     */
    private function getMainMessage(): string
    {
        switch ($this->type) {
            case 'created':
                return 'We have successfully received your device for service. Our team will begin working on it shortly.';
            case 'status_changed':
                return 'Your device service status has been updated. Please see the details below.';
            default:
                return 'This is a notification regarding your device service.';
        }
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'device_repair_id' => $this->deviceRepair->id,
            'tracking_code' => $this->deviceRepair->tracking_code,
            'type' => $this->type,
            'status' => $this->deviceRepair->status,
        ];
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Device Service Email Failed', [
            'device_repair_id' => $this->deviceRepair->id,
            'tracking_code' => $this->deviceRepair->tracking_code,
            'type' => $this->type,
            'user_email' => $this->deviceRepair->user->email,
            'user_name' => $this->deviceRepair->user->name,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }

    /**
     * Handle successful email delivery.
     */
    public function sent($notifiable, $channel): void
    {
        Log::info('Device Service Email Sent Successfully', [
            'device_repair_id' => $this->deviceRepair->id,
            'tracking_code' => $this->deviceRepair->tracking_code,
            'type' => $this->type,
            'user_email' => $this->deviceRepair->user->email,
            'user_name' => $this->deviceRepair->user->name,
            'channel' => $channel,
            'sent_at' => now()->toDateTimeString()
        ]);
    }
}
