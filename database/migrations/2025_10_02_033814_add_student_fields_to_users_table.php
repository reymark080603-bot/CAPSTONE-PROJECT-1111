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
        Schema::table('users', function (Blueprint $table) {
            // Add student-specific fields
            $table->string('firstname')->after('library_id');
            $table->string('mi')->nullable()->after('firstname');
            $table->string('lastname')->after('mi');
            $table->string('year')->nullable()->after('role');
            $table->string('course')->nullable()->after('year');
            
            // Update role enum to include 'student'
            $table->enum('role', ['admin', 'staff', 'student'])->default('student')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['firstname', 'mi', 'lastname', 'year', 'course']);
            $table->enum('role', ['admin', 'staff', 'member'])->default('member')->change();
        });
    }
};
