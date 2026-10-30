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
        Schema::dropIfExists('cafe_reactions');
        Schema::table('cafes', function (Blueprint $table) {
            $table->dropColumn('total_energy');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('cafe_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cafe_id')->constrained()->cascadeOnDelete();
            $table->string('visitor_id');
            $table->unsignedBigInteger('energy_count')->default(0);
            $table->timestamps();
            $table->unique(['cafe_id', 'visitor_id']);
        });

        Schema::table('cafes', function (Blueprint $table) {
            $table->unsignedBigInteger('total_energy')->default(0)->after('rating');
        });
    }
};
