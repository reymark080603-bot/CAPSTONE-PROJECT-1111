<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
    ];

    /**
     * Get the users for this role
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Scope a query to find by name
     */
    public function scopeByName($query, $name)
    {
        return $query->where('name', $name);
    }

    /**
     * Check if this is a student role
     */
    public function isStudent()
    {
        return $this->name === 'student';
    }

    /**
     * Check if this is a staff role (deprecated, use librarian)
     */
    public function isStaff()
    {
        return $this->name === 'librarian';
    }

    /**
     * Check if this is a librarian role
     */
    public function isLibrarian()
    {
        return $this->name === 'librarian';
    }

    /**
     * Check if this role has admin privileges
     */
    public function hasAdminPrivileges()
    {
        return $this->name === 'admin' || $this->name === 'librarian';
    }

    /**
     * Check if this is an admin role
     */
    public function isAdmin()
    {
        return $this->name === 'admin';
    }
}
