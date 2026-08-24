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
            $table->index(['user_id', 'status'], 'idx_borrow_records_user_status');
            $table->index('due_date', 'idx_borrow_records_due_date');
            $table->index('status', 'idx_borrow_records_status');
        });

        Schema::table('books', function (Blueprint $table) {
            $table->index('title', 'idx_books_title');
            $table->index('author', 'idx_books_author');
            $table->index('resource_type', 'idx_books_resource_type');
            $table->index('campus', 'idx_books_campus');
            $table->index('created_at', 'idx_books_created_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('library_id', 'idx_users_library_id');
            $table->index('campus', 'idx_users_campus');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('borrow_records', function (Blueprint $table) {
            $table->dropIndex('idx_borrow_records_user_status');
            $table->dropIndex('idx_borrow_records_due_date');
            $table->dropIndex('idx_borrow_records_status');
        });

        Schema::table('books', function (Blueprint $table) {
            $table->dropIndex('idx_books_title');
            $table->dropIndex('idx_books_author');
            $table->dropIndex('idx_books_resource_type');
            $table->dropIndex('idx_books_campus');
            $table->dropIndex('idx_books_created_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_library_id');
            $table->dropIndex('idx_users_campus');
        });
    }
};
