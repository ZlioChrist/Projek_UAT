<?php

namespace App\Console\Commands;

use App\Services\OrderService;
use Illuminate\Console\Command;

class ExpireUnpaidOrders extends Command
{
    protected $signature = 'orders:expire-unpaid';

    protected $description = 'Cancel expired unpaid orders and release reserved stock';

    public function handle(OrderService $orders): int
    {
        $count = $orders->expireDueOrders();
        $this->info("Expired {$count} unpaid order(s).");

        return self::SUCCESS;
    }
}
