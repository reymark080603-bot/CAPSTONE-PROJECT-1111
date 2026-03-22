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
            // Add missing legacy columns if they don't exist
            if (!Schema::hasColumn('books', 'author')) {
                $table->string('author')->nullable()->after('title');
            }
            if (!Schema::hasColumn('books', 'isbn')) {
                $table->string('isbn')->nullable()->unique()->after('author');
            }
            
            // Add resource type and specialized fields
            $table->string('resource_type', 20)->default('book')->after('title');
            $table->string('volume', 50)->nullable()->after('resource_type');
            $table->string('issue', 50)->nullable()->after('volume');
            $table->string('advisor', 255)->nullable()->after('issue');
            $table->date('defense_date')->nullable()->after('advisor');
            $table->string('degree', 100)->nullable()->after('defense_date');
            
            // Add indexes for better performance
            $table->index('resource_type');
            $table->index(['resource_type', 'published_year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropIndex(['resource_type']);
            $table->dropIndex(['resource_type', 'published_year']);
            $table->dropColumn(['resource_type', 'volume', 'issue', 'advisor', 'defense_date', 'degree']);
            // Note: ISBN column is not dropped here as it might be needed separately
        });
    }
};
