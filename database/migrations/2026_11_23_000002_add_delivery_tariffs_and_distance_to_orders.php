<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cafes', function (Blueprint $table): void {
            $table->unsignedBigInteger('delivery_base_fee')->nullable()->after('bank_account_name');
            $table->unsignedBigInteger('delivery_fee_per_km')->nullable()->after('delivery_base_fee');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->decimal('delivery_distance_km', 8, 2)->nullable()->after('delivery_address');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('delivery_distance_km');
        });

        Schema::table('cafes', function (Blueprint $table): void {
            $table->dropColumn(['delivery_base_fee', 'delivery_fee_per_km']);
        });
    }
};
