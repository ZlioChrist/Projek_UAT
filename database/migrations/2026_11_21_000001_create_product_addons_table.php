<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_addons', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('addon_product_id')->constrained('products')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['parent_product_id', 'addon_product_id']);
        });

        Schema::table('order_items', function (Blueprint $table): void {
            $table->foreignId('parent_order_item_id')
                ->nullable()
                ->after('product_id')
                ->constrained('order_items')
                ->cascadeOnDelete();
            $table->index(['order_id', 'parent_order_item_id']);
        });

        app(\Database\Seeders\MenuAddonSeeder::class)->run();
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropIndex(['order_id', 'parent_order_item_id']);
            $table->dropConstrainedForeignId('parent_order_item_id');
        });

        Schema::dropIfExists('product_addons');
    }
};
