<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_feedback', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cafe_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('roastery_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('category', 24);
            $table->string('customer_name', 120);
            $table->string('customer_contact', 120);
            $table->text('message');
            $table->string('status', 20)->default('new');
            $table->text('admin_note')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_feedback');
    }
};
