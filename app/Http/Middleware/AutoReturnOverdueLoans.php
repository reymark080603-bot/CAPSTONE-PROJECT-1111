<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\BorrowRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoReturnOverdueLoans
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Find all overdue borrow records
            $overdueRecords = BorrowRecord::where('status', 'borrowed')
                ->where('due_date', '<', now())
                ->get();

            if ($overdueRecords->isNotEmpty()) {
                DB::transaction(function () use ($overdueRecords) {
                    foreach ($overdueRecords as $record) {
                        $record->update([
                            'status' => 'returned',
                            'returned_date' => now(),
                            'notes' => ($record->notes ? $record->notes . ' | ' : '') . 'Auto-returned after due date (Middleware)'
                        ]);
                        
                        // Update book availability if relations exist
                        if ($record->book) {
                            $record->book->update(['availability_status' => 'available']);
                        }
                    }
                });
            }
        } catch (\Exception $e) {
            // Silently fail to avoid blocking the user if there's any database issues
            Log::error('AutoReturnOverdueLoans middleware failed: ' . $e->getMessage());
        }

        return $next($request);
    }
}
