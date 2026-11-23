<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('orders')
            ->where('status', 'awaiting_payment')
            ->where('payment_method', 'cash')
            ->whereNull('payment_expires_at')
            ->whereNotNull('created_at')
            ->orderBy('id')
            ->chunkById(100, function ($orders): void {
                foreach ($orders as $order) {
                    DB::table('orders')
                        ->where('id', $order->id)
                        ->update([
                            'payment_expires_at' => Carbon::parse($order->created_at, config('app.timezone'))
                                ->addMinutes((int) config('payment.cash_reservation_minutes', 30)),
                        ]);
                }
            });
    }

    public function down(): void
    {
        DB::table('orders')
            ->where('status', 'awaiting_payment')
            ->where('payment_method', 'cash')
            ->update(['payment_expires_at' => null]);
    }
};
