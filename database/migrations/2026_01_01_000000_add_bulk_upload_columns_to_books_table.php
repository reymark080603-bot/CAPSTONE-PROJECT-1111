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
        Schema::table('books', function (Blueprint $table) {
            // Add 'year' column if it doesn't exist (for bulk upload)
            if (!Schema::hasColumn('books', 'year')) {
                $table->integer('year')->nullable()->after('published_year');
            }
            
            // Add 'program' column if it doesn't exist (for bulk upload)
            if (!Schema::hasColumn('books', 'program')) {
                $table->string('program', 50)->nullable()->after('course');
            }
            
            // Add 'file_path' column if it doesn't exist (for bulk upload)
            if (!Schema::hasColumn('books', 'file_path')) {
                $table->string('file_path')->nullable()->after('pdf_file');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            if (Schema::hasColumn('books', 'year')) {
                $table->dropColumn('year');
            }
            if (Schema::hasColumn('books', 'program')) {
                $table->dropColumn('program');
            }
            if (Schema::hasColumn('books', 'file_path')) {
                $table->dropColumn('file_path');
            }
        });
    }
};

