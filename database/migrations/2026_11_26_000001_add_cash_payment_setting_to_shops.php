<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cafes', function (Blueprint $table): void {
            $table->boolean('accepts_cash')->default(true);
        });

        Schema::table('roasteries', function (Blueprint $table): void {
            $table->boolean('accepts_cash')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('roasteries', function (Blueprint $table): void {
            $table->dropColumn('accepts_cash');
        });

        Schema::table('cafes', function (Blueprint $table): void {
            $table->dropColumn('accepts_cash');
        });
    }
};
