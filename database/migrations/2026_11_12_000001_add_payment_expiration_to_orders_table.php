<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->timestamp('payment_expires_at')->nullable()->after('payment_method');
            $table->index(['status', 'payment_expires_at']);
        });

        DB::table('orders')
            ->where('status', 'awaiting_payment')
            ->whereIn('payment_method', ['qris', 'transfer'])
            ->whereNotNull('created_at')
            ->orderBy('id')
            ->chunkById(100, function ($orders): void {
                foreach ($orders as $order) {
                    DB::table('orders')
                        ->where('id', $order->id)
                        ->update([
                            'payment_expires_at' => Carbon::parse($order->created_at, config('app.timezone'))
                                ->addMinutes((int) config('payment.reservation_minutes', 15)),
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex(['status', 'payment_expires_at']);
            $table->dropColumn('payment_expires_at');
        });
    }
};
