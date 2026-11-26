<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_prompt_previews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('prompt_key', 160);
            $table->string('image_path');
            $table->string('image_mime_type', 64);
            $table->timestamps();

            $table->unique(['user_id', 'prompt_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_prompt_previews');
    }
};
