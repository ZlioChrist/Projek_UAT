<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_feedback', function (Blueprint $table): void {
            $table->foreignId('order_id')
                ->nullable()
                ->unique()
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customer_feedback', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('order_id');
        });
    }
};
