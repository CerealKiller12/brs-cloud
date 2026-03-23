<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CatalogVersionChanged implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public int $storeId,
        public string $storeCode,
        public int $catalogVersion,
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel("catalog.store.{$this->storeId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'catalog.version.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'storeId' => $this->storeId,
            'storeCode' => $this->storeCode,
            'catalogVersion' => $this->catalogVersion,
            'emittedAt' => now()->toIso8601String(),
        ];
    }
}
