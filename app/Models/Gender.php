<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gender extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'abbreviation',
    ];

    /**
     * Get the users for this gender
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Scope a query to find by abbreviation
     */
    public function scopeByAbbreviation($query, $abbreviation)
    {
        return $query->where('abbreviation', $abbreviation);
    }

    /**
     * Get the display name with abbreviation
     */
    public function getDisplayNameAttribute()
    {
        return "{$this->name} ({$this->abbreviation})";
    }
}
