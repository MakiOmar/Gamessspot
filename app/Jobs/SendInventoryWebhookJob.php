<?php

namespace App\Jobs;

use App\Services\WebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendInventoryWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $gameId;
    public $platform;
    public $type;
    public $stock;
    public $event;
    
    public function __construct($gameId, $platform, $type, $stock = null, $event = 'order_created')
    {
        $this->gameId = $gameId;
        $this->platform = $platform;
        $this->type = $type;
        $this->stock = $stock;
        $this->event = $event;
    }
    
    public function handle()
    {
        $webhookService = new WebhookService();
        $webhookService->notifyInventoryUpdate(
            $this->gameId,
            $this->platform,
            $this->type,
            $this->stock,
            $this->event
        );
    }
}
