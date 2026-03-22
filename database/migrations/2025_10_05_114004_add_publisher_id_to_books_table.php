<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('books', 'publisher_id')) {
            Schema::table('books', function (Blueprint $table) {
                $table->foreignId('publisher_id')->nullable()->after('publisher')->constrained('publishers')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropConstrainedForeignId('publisher_id');
        });
    }
};
