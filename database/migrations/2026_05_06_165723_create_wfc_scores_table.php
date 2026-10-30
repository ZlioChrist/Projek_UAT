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
        Schema::create('wfc_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cafe_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            $table->unsignedTinyInteger('wifi_rating');
            $table->unsignedTinyInteger('outlet_rating');
            $table->unsignedTinyInteger('comfort_rating');
            
            $table->boolean('is_verified')->default(false);
            $table->decimal('user_lat', 10, 8)->nullable();
            $table->decimal('user_lng', 11, 8)->nullable();
            
            $table->text('comment')->nullable();
            
            $table->timestamps();
            
            // Prevent duplicate reviews from the same user for the same cafe on the same day (optional but good)
            // $table->unique(['user_id', 'cafe_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wfc_scores');
    }
};
