<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->unsignedBigInteger('cafe_id')->nullable()->change();
            $table->foreignId('roastery_id')->nullable()->after('cafe_id')->constrained()->restrictOnDelete();
            $table->index(['roastery_id', 'is_active']);
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('roastery_id')->nullable()->after('cafe_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('cafe_id')->nullable()->change();
            $table->string('payment_method', 20)->default('qris')->after('payment_status');
        });

        Schema::table('order_payments', function (Blueprint $table): void {
            $table->string('payment_method', 20)->default('qris')->after('amount');
        });

        Schema::table('roasteries', function (Blueprint $table): void {
            $table->string('payment_qr_path')->nullable();
            $table->text('payment_instructions')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('roasteries', function (Blueprint $table): void {
            $table->dropColumn(['payment_qr_path', 'payment_instructions']);
        });

        Schema::table('order_payments', function (Blueprint $table): void {
            $table->dropColumn('payment_method');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('roastery_id');
            $table->dropColumn('payment_method');
            $table->unsignedBigInteger('cafe_id')->nullable(false)->change();
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropIndex(['roastery_id', 'is_active']);
            $table->dropConstrainedForeignId('roastery_id');
            $table->unsignedBigInteger('cafe_id')->nullable(false)->change();
        });
    }
};
