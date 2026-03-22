<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update all borrow records that have null or 0 borrowing_duration to 5 days
        DB::table('borrow_records')
            ->where(function($query) {
                $query->whereNull('borrowing_duration')
                      ->orWhere('borrowing_duration', 0);
            })
            ->update(['borrowing_duration' => 5]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse this fix
    }
};
