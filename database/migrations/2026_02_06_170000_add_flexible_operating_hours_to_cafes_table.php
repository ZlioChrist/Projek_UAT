<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds flexible operating hours support:
     * - is_24_hours: Boolean flag for 24-hour cafes
     * - operating_hours: JSON for weekday/weekend schedules
     */
    public function up(): void
    {
        Schema::table('cafes', function (Blueprint $table) {
            $table->boolean('is_24_hours')->default(false)->after('closing_time');
            $table->json('operating_hours')->nullable()->after('is_24_hours');
            // operating_hours structure:
            // {
            //   "weekday": { "open": "08:00", "close": "22:00" },
            //   "weekend": { "open": "10:00", "close": "23:00" }
            // }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cafes', function (Blueprint $table) {
            $table->dropColumn(['is_24_hours', 'operating_hours']);
        });
    }
};
