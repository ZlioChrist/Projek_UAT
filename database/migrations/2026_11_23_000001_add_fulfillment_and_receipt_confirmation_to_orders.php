<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('fulfillment_method', 16)->default('pickup')->after('payment_method');
            $table->text('delivery_address')->nullable()->after('fulfillment_method');
            $table->decimal('delivery_fee', 12, 2)->default(0)->after('delivery_address');
            $table->timestamp('delivery_fee_confirmed_at')->nullable()->after('delivery_fee');
            $table->timestamp('delivery_started_at')->nullable()->after('ready_at');
            $table->timestamp('receipt_confirmation_deadline_started_at')->nullable()->after('delivery_started_at');
            $table->index(['status', 'receipt_confirmation_deadline_started_at'], 'orders_receipt_confirmation_due_idx');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex('orders_receipt_confirmation_due_idx');
            $table->dropColumn([
                'fulfillment_method',
                'delivery_address',
                'delivery_fee',
                'delivery_fee_confirmed_at',
                'delivery_started_at',
                'receipt_confirmation_deadline_started_at',
            ]);
        });
    }
};
