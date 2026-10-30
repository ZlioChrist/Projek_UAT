<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Drop the reviews table
        Schema::dropIfExists('reviews');

        // 2. Update existing users with role 'user' to 'admin'
        DB::table('users')->where('role', 'user')->update(['role' => 'admin']);

        // 3. Change default value for role column in users table
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('admin')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('user')->change();
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cafe_id')->constrained()->onDelete('cascade');
            $table->string('user_name');
            $table->integer('rating');
            $table->text('comment');
            $table->timestamps();
        });
    }
};
