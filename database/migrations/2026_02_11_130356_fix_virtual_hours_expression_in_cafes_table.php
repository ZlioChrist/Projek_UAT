<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Kept as a no-op for migration history compatibility. The preceding
        // migration now creates portable TIME columns synchronized by Cafe::saving.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No schema change is performed by this compatibility migration.
    }
};
