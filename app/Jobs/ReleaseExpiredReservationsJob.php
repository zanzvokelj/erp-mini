<?php
namespace App\Jobs;

use App\Services\InventoryService;

class ReleaseExpiredReservationsJob
{
    public function handle(InventoryService $inventoryService): void
    {
        $inventoryService->releaseExpiredReservations();
    }
}
