<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tongkrongan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tongkrongan_id')->constrained()->onDelete('cascade');
            $table->foreignId('cafe_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['tongkrongan_id', 'cafe_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tongkrongan_items');
    }
};
