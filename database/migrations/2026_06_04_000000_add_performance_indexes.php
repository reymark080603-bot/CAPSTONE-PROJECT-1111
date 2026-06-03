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
        Schema::table('borrow_records', function (Blueprint $table) {
            $table->index('borrowed_date');
            $table->index('returned_date');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('role_id');
            $table->index('course_id');
            $table->index('year_level_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('borrow_records', function (Blueprint $table) {
            $table->dropIndex(['borrowed_date']);
            $table->dropIndex(['returned_date']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role_id']);
            $table->dropIndex(['course_id']);
            $table->dropIndex(['year_level_id']);
        });
    }
};
