<?php

namespace App\Console\Commands;

use App\Services\OrderService;
use Illuminate\Console\Command;

class CompleteUnconfirmedOrders extends Command
{
    protected $signature = 'orders:complete-unconfirmed';

    protected $description = 'Complete orders 24 hours after pickup readiness or delivery start when no issue was reported';

    public function handle(OrderService $orders): int
    {
        $count = $orders->completeUnconfirmedOrders();
        $this->info("Completed {$count} unconfirmed order(s).");

        return self::SUCCESS;
    }
}
