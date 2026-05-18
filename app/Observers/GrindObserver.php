<?php

namespace App\Observers;

use App\Models\Grind;
use App\Services\WalletService;

class GrindObserver
{
    public function __construct(private WalletService $walletService) {}

    /**
     * Handle the Grind "deleting" event.
     * Sync wallet before grind is deleted to ensure balance is updated.
     */
    public function deleting(Grind $grind): void
    {
        // Only sync if the deleted grind was completed (had wallet impact)
        if ($grind->status === 'completed') {
            $this->walletService->syncWallet($grind->pilot);
        }
    }
}
