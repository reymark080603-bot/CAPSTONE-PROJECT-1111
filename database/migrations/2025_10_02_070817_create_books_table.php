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
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('author');
            $table->string('isbn')->nullable()->unique();
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->integer('published_year')->nullable();
            $table->enum('availability_status', ['available', 'borrowed', 'reserved', 'maintenance'])->default('available');
            $table->string('course')->nullable();
            $table->string('year_level')->nullable();
            $table->decimal('rating', 2, 1)->default(0);
            $table->integer('copies_total')->default(1);
            $table->integer('copies_available')->default(1);
            $table->string('publisher')->nullable();
            $table->integer('pages')->nullable();
            $table->string('language')->default('English');
            $table->timestamps();
            
            $table->index(['title', 'author']);
            $table->index(['category']);
            $table->index(['course', 'year_level']);
            $table->index(['availability_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
