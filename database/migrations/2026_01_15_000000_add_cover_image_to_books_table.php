<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds cover_image column for storing auto-generated PDF cover thumbnails.
     * The cover_image field stores the path to the generated cover image.
     */
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            // Add cover_image column for PDF cover thumbnails
            $table->string('cover_image')->nullable()->after('cover_photo');
            
            // Add index for faster lookups
            $table->index('cover_image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn('cover_image');
        });
    }
};

