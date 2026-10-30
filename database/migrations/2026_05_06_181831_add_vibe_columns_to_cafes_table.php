<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cafes', function (Blueprint $table) {
            $table->string('current_vibe', 10)->nullable()->after('wfc_review_count');
            $table->timestamp('vibe_updated_at')->nullable()->after('current_vibe');
        });
    }

    public function down(): void
    {
        Schema::table('cafes', function (Blueprint $table) {
            $table->dropColumn(['current_vibe', 'vibe_updated_at']);
        });
    }
};
