<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Publisher extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function books()
    {
        return $this->hasMany(Book::class);
    }
    
    /**
     * Get the number of books for this publisher
     */
    public function getBookCountAttribute()
    {
        return $this->books()->count();
    }
}
