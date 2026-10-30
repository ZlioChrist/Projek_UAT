<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receipt_print_jobs', function (Blueprint $table): void {
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reported_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('receipt_print_jobs', function (Blueprint $table): void {
            $table->dropForeign(['reported_by']);
            $table->dropColumn(['reported_by', 'reported_at']);
        });
    }
};
