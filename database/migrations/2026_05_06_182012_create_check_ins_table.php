<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('check_ins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('cafe_id')->constrained()->onDelete('cascade');
            $table->boolean('is_verified')->default(false);
            $table->decimal('user_lat', 10, 8)->nullable();
            $table->decimal('user_lng', 11, 8)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'cafe_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('check_ins');
    }
};
