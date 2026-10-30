<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vibe_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cafe_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('level', ['sepi', 'lumayan', 'rame', 'penuh']);
            $table->boolean('is_verified')->default(false);
            $table->decimal('user_lat', 10, 8)->nullable();
            $table->decimal('user_lng', 11, 8)->nullable();
            $table->string('fingerprint', 64)->nullable();
            $table->timestamps();

            // Temporal index for aggregate queries (last 4 hours)
            $table->index(['cafe_id', 'created_at']);
            // Dedup: 1 vote per fingerprint per cafe per day
            $table->unique(['cafe_id', 'fingerprint', 'created_at'], 'vibe_dedup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vibe_votes');
    }
};
