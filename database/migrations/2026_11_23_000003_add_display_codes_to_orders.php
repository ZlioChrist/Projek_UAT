<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $existingOrderCount = DB::table('orders')->count();
        if ($existingOrderCount > 9999) {
            throw new RuntimeException('Cannot backfill WadahNgopi display codes: more than 9,999 orders already exist.');
        }

        Schema::create('order_display_code_sequences', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->unsignedSmallInteger('next_value');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->string('display_code', 7)->nullable()->unique();
        });

        $nextValue = 1;
        DB::table('orders')->select('id')->orderBy('id')->chunkById(1000, function ($orders) use (&$nextValue): void {
            foreach ($orders as $order) {
                DB::table('orders')
                    ->where('id', $order->id)
                    ->update(['display_code' => 'WN-'.str_pad((string) $nextValue, 4, '0', STR_PAD_LEFT)]);
                $nextValue++;
            }
        });

        DB::table('order_display_code_sequences')->insert([
            'id' => 1,
            'next_value' => $nextValue,
        ]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropUnique(['display_code']);
            $table->dropColumn('display_code');
        });

        Schema::dropIfExists('order_display_code_sequences');
    }
};
