<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['cafes', 'roasteries'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('bank_name', 100)->nullable();
                $table->string('bank_account_number', 50)->nullable();
                $table->string('bank_account_name', 120)->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['cafes', 'roasteries'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn(['bank_name', 'bank_account_number', 'bank_account_name']);
            });
        }
    }
};
