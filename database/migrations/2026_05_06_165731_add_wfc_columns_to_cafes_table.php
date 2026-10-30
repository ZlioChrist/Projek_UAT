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
            $table->decimal('wfc_avg_score', 3, 2)->default(0)->after('is_24_hours');
            $table->unsignedInteger('wfc_review_count')->default(0)->after('wfc_avg_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cafes', function (Blueprint $table) {
            $table->dropColumn(['wfc_avg_score', 'wfc_review_count']);
        });
    }
};
