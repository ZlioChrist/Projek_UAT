<?php

use App\Models\Cafe;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Backfill Data
        $cafes = Cafe::whereNull('operating_hours')
            ->whereNotNull('opening_time')
            ->get();

        foreach ($cafes as $cafe) {
            $open = $cafe->opening_time; // Already formatted as H:i:s usually
            $close = $cafe->closing_time;

            if ($open && $close) {
                // Ensure format H:i from H:i:s
                $open = substr($open, 0, 5);
                $close = substr($close, 0, 5);

                $cafe->operating_hours = [
                    'weekday' => ['open' => $open, 'close' => $close],
                    'weekend' => ['open' => $open, 'close' => $close],
                ];

                // Virtual columns will be updated automatically by model events/accessors if set up correctly,
                // otherwise we might need to manually set them if we are raw querying.
                // But since we use Eloquent here, and Cafe model has `HasOperatingHours` trait or logic,
                // it should work. Wait, the `saving` event in Cafe model handles virtual columns?
                // Let's check Cafe model again. Cafe::saving uses "Virtual columns handle the sync automatically in DB".
                // This refers to `GENERATED ALWAYS AS` columns in MySQL/Postgres?
                // Step 177: "Virtual columns handle the sync automatically in DB for Cafe model. Do NOT manually set..."
                // This typically means the column is defined as `GENERATED ALWAYS AS (json_unquote(json_extract...)) VIRTUAL`.
                // If so, just updating `operating_hours` JSON is enough!

                $cafe->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse needed as we are just filling data.
    }
};
