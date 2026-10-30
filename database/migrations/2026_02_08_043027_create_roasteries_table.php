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
        Schema::create('roasteries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->text('address')->nullable();
            $table->text('google_maps_url')->nullable();
            $table->string('whatsapp_number')->nullable();
            $table->string('image_path')->nullable();
            $table->json('images')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->json('social_links')->nullable();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('draft');
            $table->timestamps();

            $table->index('status');
            $table->index(['latitude', 'longitude']);
            $table->index('owner_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roasteries');
    }
};
