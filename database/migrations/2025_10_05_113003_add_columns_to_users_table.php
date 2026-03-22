<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'firstname')) {
                $table->string('firstname')->nullable()->after('name');
            }
            if (!Schema::hasColumn('users', 'mi')) {
                $table->string('mi')->nullable()->after('firstname');
            }
            if (!Schema::hasColumn('users', 'lastname')) {
                $table->string('lastname')->nullable()->after('mi');
            }
            if (!Schema::hasColumn('users', 'library_id')) {
                $table->string('library_id')->nullable()->after('lastname');
            }
            if (!Schema::hasColumn('users', 'year')) {
                $table->string('year')->nullable()->after('library_id');
            }
            if (!Schema::hasColumn('users', 'course')) {
                $table->string('course')->nullable()->after('year');
            }
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('student')->after('password');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'firstname',
                'mi',
                'lastname',
                'library_id',
                'year',
                'course',
                'role',
            ]);
        });
    }
};
