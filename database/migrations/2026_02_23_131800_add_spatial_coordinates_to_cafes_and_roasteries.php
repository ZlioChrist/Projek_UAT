<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Clean up existing location column and index if they exist
        foreach (['cafes', 'roasteries'] as $tableName) {
            try {
                // Try dropping spatial index first
                DB::statement("ALTER TABLE {$tableName} DROP INDEX IF EXISTS location");
            } catch (\Exception $e) {
            }

            try {
                // Try dropping column
                DB::statement("ALTER TABLE {$tableName} DROP COLUMN IF EXISTS location");
            } catch (\Exception $e) {
            }
        }

        // 2. Add nullable POINT columns without a strict SRID. This remains
        // portable across MariaDB versions bundled with XAMPP; location search
        // uses the latitude/longitude Haversine fallback where spatial support differs.
        Schema::table('cafes', function (Blueprint $table) {
            $table->geometry('location', 'point')->nullable()->after('longitude');
        });

        Schema::table('roasteries', function (Blueprint $table) {
            $table->geometry('location', 'point')->nullable()->after('longitude');
        });

        // 3. Store points when the database supports the geometry functions.
        // A null location is valid; lat/lng remain the canonical fallback fields.
        try {
            DB::statement("UPDATE cafes SET location = ST_GeomFromText(CONCAT('POINT(', latitude, ' ', longitude, ')')) WHERE latitude IS NOT NULL AND longitude IS NOT NULL");
            DB::statement("UPDATE roasteries SET location = ST_GeomFromText(CONCAT('POINT(', latitude, ' ', longitude, ')')) WHERE latitude IS NOT NULL AND longitude IS NOT NULL");
        } catch (\Throwable $exception) {
            // Do not block installation when a supported SQL engine lacks spatial functions.
        }
    }

    public function down(): void
    {
        Schema::table('cafes', function (Blueprint $table) {
            $table->dropColumn('location');
        });

        Schema::table('roasteries', function (Blueprint $table) {
            $table->dropColumn('location');
        });
    }
};
