<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'department',
    ];

    /**
     * Get the users for this course
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Scope a query to find by code
     */
    public function scopeByCode($query, $code)
    {
        return $query->where('code', $code);
    }

    /**
     * Scope a query to find by department
     */
    public function scopeByDepartment($query, $department)
    {
        return $query->where('department', $department);
    }
}
