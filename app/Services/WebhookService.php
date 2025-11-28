<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    /**
     * WordPress webhook endpoint URL
     */
    private $webhookUrl;
    
    /**
     * Webhook secret key for authentication
     */
    private $webhookSecret;
    
    public function __construct()
    {
        $this->webhookUrl = env('WORDPRESS_WEBHOOK_URL');
        $this->webhookSecret = env('WORDPRESS_WEBHOOK_SECRET');
    }
    
    /**
     * Send inventory update webhook to WordPress
     *
     * @param int    $gameId
     * @param string $platform (e.g., '4' or '5')
     * @param string $type (e.g., 'primary', 'secondary', 'offline')
     * @param int    $stock (optional)
     * @param string $event (default: 'order_created')
     * @return bool
     */
    public function notifyInventoryUpdate($gameId, $platform, $type, $stock = null, $event = 'order_created')
    {
        if (empty($this->webhookUrl) || empty($this->webhookSecret)) {
            Log::warning('Webhook not configured. Skipping notification.');
            return false;
        }
        
        // Prepare payload
        $payload = [
            'event'     => $event,
            'game_id'   => $gameId,
            'platform'  => $platform,
            'type'      => $type,
            'stock'     => $stock,
            'timestamp' => now()->toIso8601String(),
        ];
        
        // Convert to JSON
        $jsonPayload = json_encode($payload);
        
        // Generate HMAC signature
        $signature = hash_hmac('sha256', $jsonPayload, $this->webhookSecret);
        
        try {
            // Send webhook (non-blocking)
            $response = Http::timeout(5)
                ->withHeaders([
                    'Content-Type'          => 'application/json',
                    'X-Gamesspot-Signature' => $signature,
                ])
                ->post($this->webhookUrl, $payload);
            
            if ($response->successful()) {
                return true;
            } else {
                Log::error('Webhook failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Webhook exception: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send webhook asynchronously (recommended)
     * Dispatch as a queued job for better performance
     */
    public function notifyInventoryUpdateAsync($gameId, $platform, $type, $stock = null, $event = 'order_created')
    {
        dispatch(function () use ($gameId, $platform, $type, $stock, $event) {
            $this->notifyInventoryUpdate($gameId, $platform, $type, $stock, $event);
        })->afterResponse();
    }
}
