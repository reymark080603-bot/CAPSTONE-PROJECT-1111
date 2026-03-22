<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YearLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'level',
        'numeric_level',
    ];

    /**
     * Get the users for this year level
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Scope a query to find by numeric level
     */
    public function scopeByNumericLevel($query, $level)
    {
        return $query->where('numeric_level', $level);
    }

    /**
     * Get the display name with numeric level
     */
    public function getDisplayNameAttribute()
    {
        return "{$this->level} ({$this->numeric_level})";
    }
}
