<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('bookmarkable_id');
            $table->string('bookmarkable_type', 50); // 'cafe' or 'roastery'
            $table->timestamps();

            $table->unique(['user_id', 'bookmarkable_id', 'bookmarkable_type'], 'bookmark_unique');
            $table->index(['user_id', 'bookmarkable_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookmarks');
    }
};
