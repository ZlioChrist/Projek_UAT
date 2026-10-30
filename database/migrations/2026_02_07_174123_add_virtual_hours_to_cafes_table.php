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
        Schema::table('cafes', function (Blueprint $table) {
            // Plain indexed TIME columns are more portable than indexed JSON-generated
            // columns across MariaDB versions shipped with XAMPP.
            $table->time('weekday_open')->nullable()->index();
            $table->time('weekday_close')->nullable()->index();
            $table->time('weekend_open')->nullable()->index();
            $table->time('weekend_close')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cafes', function (Blueprint $table) {
            $table->dropColumn(['weekday_open', 'weekday_close', 'weekend_open', 'weekend_close']);
        });
    }
};
