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
            // Add back the legacy columns that were dropped but still being used
            if (!Schema::hasColumn('books', 'author')) {
                $table->string('author')->nullable()->after('title');
            }
            if (!Schema::hasColumn('books', 'category')) {
                $table->string('category')->nullable()->after('description');
            }
            if (!Schema::hasColumn('books', 'publisher')) {
                $table->string('publisher')->nullable()->after('course');
            }
            if (!Schema::hasColumn('books', 'published_year')) {
                $table->string('published_year')->nullable()->after('publisher');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn(['author', 'category', 'publisher', 'published_year']);
        });
    }
};
