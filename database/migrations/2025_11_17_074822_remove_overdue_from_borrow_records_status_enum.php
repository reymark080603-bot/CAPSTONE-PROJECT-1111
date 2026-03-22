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
        // Convert any 'overdue' status to 'returned' since auto-return handles overdue books
        DB::table('borrow_records')
            ->where('status', 'overdue')
            ->update(['status' => 'returned']);

        // Note: In MySQL, changing enum values requires recreating the column
        // Since this is a cleanup migration and 'overdue' is no longer needed,
        // we'll leave the enum as is to avoid data loss risks
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse needed as this is a cleanup migration
    }
};
