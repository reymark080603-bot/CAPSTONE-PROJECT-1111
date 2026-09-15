<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function indexExists(string $table, string $indexName): bool
    {
        $result = DB::select(

            [$indexName]
        );

        return count($result) > 0;
    }

    private function addIndexIfMissing(Blueprint $table, string $tableName, array|string $columns, string $indexName): void
    {
        if (! $this->indexExists($tableName, $indexName)) {
            $table->index($columns, $indexName);
        }
    }

    private function dropIndexIfExists(Blueprint $table, string $tableName, string $indexName): void
    {
        if ($this->indexExists($tableName, $indexName)) {
            $table->dropIndex($indexName);
        }
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('borrow_records', function (Blueprint $table) {
            $this->addIndexIfMissing($table, 'borrow_records', ['user_id', 'status'], 'idx_borrow_records_user_status');
            $this->addIndexIfMissing($table, 'borrow_records', 'due_date', 'idx_borrow_records_due_date');
            $this->addIndexIfMissing($table, 'borrow_records', 'status', 'idx_borrow_records_status');
        });

        Schema::table('books', function (Blueprint $table) {
            $this->addIndexIfMissing($table, 'books', 'title', 'idx_books_title');
            $this->addIndexIfMissing($table, 'books', 'author', 'idx_books_author');
            $this->addIndexIfMissing($table, 'books', 'resource_type', 'idx_books_resource_type');
            $this->addIndexIfMissing($table, 'books', 'created_at', 'idx_books_created_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $this->addIndexIfMissing($table, 'users', 'library_id', 'idx_users_library_id');
            $this->addIndexIfMissing($table, 'users', 'campus', 'idx_users_campus');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('borrow_records', function (Blueprint $table) {
            $this->dropIndexIfExists($table, 'borrow_records', 'idx_borrow_records_user_status');
            $this->dropIndexIfExists($table, 'borrow_records', 'idx_borrow_records_due_date');
            $this->dropIndexIfExists($table, 'borrow_records', 'idx_borrow_records_status');
        });

        Schema::table('books', function (Blueprint $table) {
            $this->dropIndexIfExists($table, 'books', 'idx_books_title');
            $this->dropIndexIfExists($table, 'books', 'idx_books_author');
            $this->dropIndexIfExists($table, 'books', 'idx_books_resource_type');
            $this->dropIndexIfExists($table, 'books', 'idx_books_created_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $this->dropIndexIfExists($table, 'users', 'idx_users_library_id');
            $this->dropIndexIfExists($table, 'users', 'idx_users_campus');
        });
    }
};
