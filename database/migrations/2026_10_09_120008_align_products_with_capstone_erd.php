<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('menu_items')) {
            if (Schema::hasTable('products')) {
                throw new RuntimeException('Tabel products sudah ada; periksa dan migrasikan data secara manual sebelum menyelaraskan katalog capstone.');
            }

            if (Schema::hasTable('order_items') && Schema::hasColumn('order_items', 'menu_item_id')) {
                Schema::table('order_items', function (Blueprint $table): void {
                    $table->dropForeign(['menu_item_id']);
                });
            }

            Schema::rename('menu_items', 'products');
        }

        if (Schema::hasTable('order_items') && Schema::hasColumn('order_items', 'menu_item_id')) {
            Schema::table('order_items', function (Blueprint $table): void {
                $table->renameColumn('menu_item_id', 'product_id');
            });
        }

        if (Schema::hasTable('order_items') && Schema::hasColumn('order_items', 'product_id')) {
            Schema::table('order_items', function (Blueprint $table): void {
                $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        if (Schema::hasTable('order_items') && Schema::hasColumn('order_items', 'product_id')) {
            Schema::table('order_items', function (Blueprint $table): void {
                $table->dropForeign(['product_id']);
            });
        }

        Schema::rename('products', 'menu_items');

        if (Schema::hasTable('order_items') && Schema::hasColumn('order_items', 'product_id')) {
            Schema::table('order_items', function (Blueprint $table): void {
                $table->renameColumn('product_id', 'menu_item_id');
                $table->foreign('menu_item_id')->references('id')->on('menu_items')->nullOnDelete();
            });
        }
    }
};
