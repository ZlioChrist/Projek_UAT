<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('products') || Schema::hasColumn('products', 'category')) {
            return;
        }

        Schema::table('products', function (Blueprint $table): void {
            $table->string('category', 100)->nullable()->after('name');
            $table->index(['cafe_id', 'category'], 'products_cafe_category_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('products') || ! Schema::hasColumn('products', 'category')) {
            return;
        }

        Schema::table('products', function (Blueprint $table): void {
            $table->dropIndex('products_cafe_category_index');
            $table->dropColumn('category');
        });
    }
};
