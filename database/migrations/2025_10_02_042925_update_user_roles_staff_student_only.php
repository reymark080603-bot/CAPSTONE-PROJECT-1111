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
            // Ensure role column exists then adjust enum values
            if (Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['staff', 'student'])->default('student')->change();
            } else {
                $table->enum('role', ['staff', 'student'])->default('student')->after('email');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Revert back to previous roles if needed
            $table->enum('role', ['admin', 'staff', 'student'])->default('student')->change();
        });
    }
};
