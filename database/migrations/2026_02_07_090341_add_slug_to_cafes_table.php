<?php

use App\Models\Cafe;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cafes', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
        });

        // Generate slugs for existing cafes
        Cafe::all()->each(function ($cafe) {
            $slug = Str::slug($cafe->name);
            $originalSlug = $slug;
            $count = 1;

            // Ensure uniqueness
            while (Cafe::where('slug', $slug)->exists()) {
                $slug = $originalSlug.'-'.$count++;
            }

            $cafe->update(['slug' => $slug]);
        });

        // After populating, we can optionally make it non-nullable if we want to be strict
        // Schema::table('cafes', function (Blueprint $table) {
        //    $table->string('slug')->nullable(false)->change();
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cafes', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
