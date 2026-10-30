<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Use raw SQL to ensure binary type change is handled correctly by MySQL
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE cache MODIFY value MEDIUMBLOB');
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE sessions MODIFY payload MEDIUMBLOB');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE cache MODIFY value MEDIUMTEXT');
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE sessions MODIFY payload LONGTEXT');
    }
};
