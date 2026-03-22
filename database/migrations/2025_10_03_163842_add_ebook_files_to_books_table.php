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
            $table->string('epub_file')->nullable()->after('pdf_file');
            $table->string('doc_file')->nullable()->after('epub_file'); 
            $table->string('file_type')->nullable()->after('doc_file'); // pdf, epub, doc, or html
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn(['epub_file', 'doc_file', 'file_type']);
        });
    }
};
