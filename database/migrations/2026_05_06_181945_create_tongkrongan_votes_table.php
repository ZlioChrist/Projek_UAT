<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tongkrongan_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tongkrongan_item_id')->constrained()->onDelete('cascade');
            $table->string('voter_fingerprint', 64);
            $table->foreignId('voter_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->unique(['tongkrongan_item_id', 'voter_fingerprint'], 'tongkrongan_vote_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tongkrongan_votes');
    }
};
