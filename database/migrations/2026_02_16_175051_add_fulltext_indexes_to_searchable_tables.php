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
        Schema::table('cafes', function (Blueprint $table) {
            $table->fullText(['name', 'address', 'description']);
        });

        Schema::table('roasteries', function (Blueprint $table) {
            $table->fullText(['name', 'address', 'description']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cafes', function (Blueprint $table) {
            $table->dropFullText(['name', 'address', 'description']);
        });

        Schema::table('roasteries', function (Blueprint $table) {
            $table->dropFullText(['name', 'address', 'description']);
        });
    }
};
