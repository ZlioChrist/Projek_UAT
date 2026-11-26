<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gemini_chat_conversations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 120);
            $table->timestamps();

            $table->index(['user_id', 'updated_at']);
        });

        Schema::create('gemini_chat_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')->constrained('gemini_chat_conversations')->cascadeOnDelete();
            $table->string('role', 16);
            $table->longText('content');
            $table->string('image_path')->nullable();
            $table->string('image_mime_type', 64)->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gemini_chat_messages');
        Schema::dropIfExists('gemini_chat_conversations');
    }
};
