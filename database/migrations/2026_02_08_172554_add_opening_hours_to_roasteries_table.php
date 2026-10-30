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
        Schema::table('roasteries', function (Blueprint $table) {
            $table->boolean('is_24_hours')->default(false);
            $table->json('operating_hours')->nullable();

            // Explicit columns for simple queries (Open Now logic)
            $table->time('weekday_open')->nullable();
            $table->time('weekday_close')->nullable();
            $table->time('weekend_open')->nullable();
            $table->time('weekend_close')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roasteries', function (Blueprint $table) {
            $table->dropColumn([
                'is_24_hours',
                'operating_hours',
                'weekday_open',
                'weekday_close',
                'weekend_open',
                'weekend_close',
            ]);
        });
    }
};
