<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cafe_location_points', function (Blueprint $table): void {
            $table->string('address')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('cafe_location_points', function (Blueprint $table): void {
            $table->dropColumn('address');
        });
    }
};
