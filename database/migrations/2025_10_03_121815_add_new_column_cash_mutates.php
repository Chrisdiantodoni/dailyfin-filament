<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cash_mutates', function (Blueprint $table) {
            $table->integer('neq_expenses')->nullable();
            $table->integer('neq_incomes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_mutates', function (Blueprint $table) {
            $table->dropColumn('neq_expenses');
            $table->dropColumn('neq_incomes');
        });
    }
};
