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
            if (!Schema::hasColumn('books', 'publisher_id')) {
                $table->unsignedBigInteger('publisher_id')->nullable()->after('course');
            }
            if (!Schema::hasColumn('books', 'published_year')) {
                $table->integer('published_year')->nullable()->after('publisher_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            if (Schema::hasColumn('books', 'published_year')) {
                $table->dropColumn('published_year');
            }
            if (Schema::hasColumn('books', 'publisher_id')) {
                $table->dropColumn('publisher_id');
            }
        });
    }
};
