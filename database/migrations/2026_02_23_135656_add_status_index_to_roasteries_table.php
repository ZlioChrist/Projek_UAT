<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Backfill: Re-sync weekend_close from operating_hours JSON
        // This fixes data corruption caused by the duplicate weekend_open bug in Roastery model
        \App\Models\Roastery::whereNotNull('operating_hours')->each(function ($roastery) {
            $hours = $roastery->operating_hours;
            if (isset($hours['weekend']['close'])) {
                \Illuminate\Support\Facades\DB::table('roasteries')
                    ->where('id', $roastery->id)
                    ->update(['weekend_close' => $hours['weekend']['close']]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data backfill only — no schema changes to reverse
    }
};
