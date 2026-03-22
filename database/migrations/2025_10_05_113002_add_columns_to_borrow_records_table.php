<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('borrow_records', function (Blueprint $table) {
            if (!Schema::hasColumn('borrow_records', 'borrowing_duration')) {
                $table->integer('borrowing_duration')->nullable()->after('status');
            }
            if (!Schema::hasColumn('borrow_records', 'renewal_count')) {
                $table->integer('renewal_count')->default(0)->after('borrowing_duration');
            }
        });
    }

    public function down(): void
    {
        Schema::table('borrow_records', function (Blueprint $table) {
            $table->dropColumn([
                'borrowing_duration',
                'renewal_count',
            ]);
        });
    }
};
