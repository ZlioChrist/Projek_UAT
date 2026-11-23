<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('google_id')->nullable()->unique();
            $table->string('phone_number', 20)->nullable()->unique();
            $table->timestamp('phone_verified_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['google_id']);
            $table->dropUnique(['phone_number']);
            $table->dropColumn(['google_id', 'phone_number', 'phone_verified_at']);
        });
    }
};
