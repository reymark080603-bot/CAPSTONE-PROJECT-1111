<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            if (!Schema::hasColumn('books', 'cover_photo')) {
                $table->string('cover_photo')->nullable()->after('description');
            }
            if (!Schema::hasColumn('books', 'pdf_file')) {
                $table->string('pdf_file')->nullable()->after('cover_photo');
            }
            if (!Schema::hasColumn('books', 'epub_file')) {
                $table->string('epub_file')->nullable()->after('pdf_file');
            }
            if (!Schema::hasColumn('books', 'doc_file')) {
                $table->string('doc_file')->nullable()->after('epub_file');
            }
            if (!Schema::hasColumn('books', 'file_type')) {
                $table->string('file_type')->nullable()->after('doc_file');
            }
            if (!Schema::hasColumn('books', 'content')) {
                $table->longText('content')->nullable()->after('file_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn([
                'cover_photo',
                'pdf_file',
                'epub_file',
                'doc_file',
                'file_type',
                'content',
            ]);
        });
    }
};
