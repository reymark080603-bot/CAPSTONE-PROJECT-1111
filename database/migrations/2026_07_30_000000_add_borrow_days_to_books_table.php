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
        if (!Schema::hasColumn('books', 'borrow_days')) {
            Schema::table('books', function (Blueprint $table) {
                $table->integer('borrow_days')->default(5)->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('books', 'borrow_days')) {
            Schema::table('books', function (Blueprint $table) {
                $table->dropColumn('borrow_days');
            });
        }
    }
};
