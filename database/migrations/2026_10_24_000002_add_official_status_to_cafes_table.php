<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cafes', function (Blueprint $table) {
            $table->boolean('official_is_open')->nullable()->after('operating_hours');
            $table->string('official_status_text', 32)->nullable()->after('official_is_open');
            $table->timestamp('official_status_checked_at')->nullable()->after('official_status_text');
        });
    }

    public function down(): void
    {
        Schema::table('cafes', function (Blueprint $table) {
            $table->dropColumn([
                'official_is_open',
                'official_status_text',
                'official_status_checked_at',
            ]);
        });
    }
};
