<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BorrowRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'book_id',
        'borrowed_date',
        'due_date',
        'returned_date',
        'status',
        'borrowing_duration',
        'renewal_count',
        'notes'
    ];

    protected $casts = [
        'borrowed_date' => 'datetime',
        'due_date' => 'datetime',
        'returned_date' => 'datetime'
    ];

    /**
     * Get the user who borrowed the book
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the book that was borrowed
     */
    public function book()
    {
        return $this->belongsTo(Book::class);
    }


    /**
     * Scope to get active borrows
     */
    public function scopeBorrowed($query)
    {
        return $query->where('status', 'borrowed');
    }

    /**
     * Scope to get returned books
     */
    public function scopeReturned($query)
    {
        return $query->where('status', 'returned');
    }

    protected static function boot()
    {
        parent::boot();

        // Auto-check for overdue books when the model is accessed
        static::retrieved(function ($borrowRecord) {
            // Check if this specific record is overdue and auto-return if needed
            if ($borrowRecord->status === 'borrowed' && $borrowRecord->due_date < now()) {
                $borrowRecord->autoReturnIfDue();
            }
        });
    }

    /**
     * Auto-return the book if past due date
     */
    public function autoReturnIfDue()
    {
        if ($this->status === 'borrowed' && $this->due_date < now()) {
            $this->update([
                'status' => 'returned',
                'returned_date' => now(),
                'notes' => ($this->notes ? $this->notes . ' | ' : '') . 'Auto-returned after due date'
            ]);
            
            // Update book availability
            $this->book->update(['availability_status' => 'available']);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Process auto-return for this record
     */
    public function processAutoReturn()
    {
        if ($this->autoReturnIfDue()) {
            // Calculate actual borrowing duration
            $duration = $this->borrowed_date->diffInDays($this->returned_date);
            $this->update(['borrowing_duration' => $duration]);
            
            return true;
        }
        return false;
    }
    
    /**
     * Check if book is eligible for auto-return
     */
    public function isEligibleForAutoReturn()
    {
        return $this->status === 'borrowed' && $this->due_date < now();
    }
    
    /**
     * Get the borrowing duration in days
     */
    public function getBorrowingDurationAttribute()
    {
        if ($this->returned_date) {
            return (int) $this->borrowed_date->diffInDays($this->returned_date);
        }
        return (int) $this->borrowed_date->diffInDays(now());
    }
    
    /**
     * Get days remaining until due date
     */
    public function getDaysRemainingAttribute()
    {
        if ($this->status !== 'borrowed' || !$this->due_date) {
            return 0;
        }
        
        $daysRemaining = (int) now()->diffInDays($this->due_date, false);
        return max(0, $daysRemaining);
    }
    
    /**
     * Get days past due date (for auto-returned books)
     */
    public function getDaysPastDueAttribute()
    {
        if ($this->status !== 'borrowed' || !$this->due_date) {
            return 0;
        }
        
        if ($this->due_date < now()) {
            return (int) $this->due_date->diffInDays(now());
        }
        
        return 0;
    }
    
    /**
     * Check if book is due for auto-return
     */
    public function isDueForAutoReturn()
    {
        return $this->status === 'borrowed' && $this->due_date < now();
    }
    
    /**
     * Get the actual duration from database or calculate it
     */
    public function getActualDurationAttribute()
    {
        // Use the stored duration if available, otherwise calculate it
        return $this->borrowing_duration ?? $this->getBorrowingDurationAttribute();
    }
    
    /**
     * Get the status color for UI display
     */
    public function getStatusColorAttribute()
    {
        switch ($this->status) {
            case 'borrowed':
                // Check if due for auto-return
                if ($this->isDueForAutoReturn()) {
                    return 'orange'; // Books that need auto-return
                }
                return 'blue';
            case 'returned':
                // Check if auto-returned
                if ($this->notes && str_contains($this->notes, 'Auto-returned')) {
                    return 'purple'; // Auto-returned books
                }
                return 'green';
            default:
                return 'gray';
        }
    }
    
    /**
     * Get status description
     */
    public function getStatusDescriptionAttribute()
    {
        switch ($this->status) {
            case 'borrowed':
                if ($this->isDueForAutoReturn()) {
                    return 'Due for Auto-Return';
                }
                return 'Active';
            case 'returned':
                if ($this->notes && str_contains($this->notes, 'Auto-returned')) {
                    return 'Auto-Returned';
                }
                return 'Returned';
            default:
                return 'Unknown';
        }
    }

    /**
     * Get the custom ID for the borrow record
     */
    public function getCustomIdAttribute()
    {
        return 'BOR-' . str_pad($this->id, 6, '0', STR_PAD_LEFT);
    }
}
