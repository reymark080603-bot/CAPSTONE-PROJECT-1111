<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'firstname',
        'mi',
        'lastname',
        'gender',
        'gender_id',
        'library_id',
        'campus',
        'year',
        'year_level_id',
        'course',
        'course_id',
        'email',
        'email_verified_at',
        'password',
        'role',
        'role_id',
        'preferences',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'preferences' => 'array',
        ];
    }
    
    /**
     * Get the borrow records for the user
     */
    public function borrowRecords()
    {
        return $this->hasMany(\App\Models\BorrowRecord::class);
    }

    /**
     * Get the course that the user belongs to
     */
    public function course()
    {
        return $this->belongsTo(\App\Models\Course::class);
    }

    /**
     * Get the year level of the user
     */
    public function yearLevel()
    {
        return $this->belongsTo(\App\Models\YearLevel::class);
    }

    /**
     * Get the role of the user
     */
    public function role()
    {
        return $this->belongsTo(\App\Models\Role::class);
    }

    /**
     * Get the gender of the user
     */
    public function gender()
    {
        return $this->belongsTo(\App\Models\Gender::class);
    }

    /**
     * Get the user's current borrowed books
     */
    public function currentBorrowedBooks()
    {
        return $this->belongsToMany(Book::class, 'borrow_records')
                    ->wherePivot('status', 'borrowed')
                    ->withPivot(['borrowed_date', 'due_date', 'status']);
    }
    
    /**
     * Check if user is a student
     */
    public function isStudent()
    {
        return $this->role?->name === 'student' || $this->role === 'student';
    }
    
    /**
     * Check if user is a librarian
     */
    public function isLibrarian()
    {
        return $this->role?->name === 'librarian' || $this->role === 'librarian';
    }

    /**
     * Check if user is an admin or librarian (both have admin privileges)
     */
    public function isAdmin()
    {
        return $this->role?->name === 'admin' || $this->role?->name === 'librarian' || $this->role === 'admin' || $this->role === 'librarian';
    }

    /**
     * Check if user has admin privileges (admin or librarian)
     */
    public function hasAdminPrivileges()
    {
        return $this->isAdmin();
    }

    /**
     * Get the user's full name
     */
    public function getFullNameAttribute()
    {
        $parts = array_filter([
            $this->firstname,
            $this->mi ? $this->mi . '.' : null,
            $this->lastname
        ]);
        
        return !empty($parts) ? implode(' ', $parts) : $this->name;
    }

    /**
     * Get the user's course name (for backward compatibility)
     */
    public function getCourseNameAttribute()
    {
        return $this->course?->name ?? $this->course;
    }

    /**
     * Get the user's year level name (for backward compatibility)
     */
    public function getYearLevelNameAttribute()
    {
        return $this->yearLevel?->level ?? $this->year;
    }

    /**
     * Get the user's role name (for backward compatibility)
     */
    public function getRoleNameAttribute()
    {
        return $this->role?->display_name ?? $this->role?->name ?? $this->role;
    }

    /**
     * Get the user's gender name (for backward compatibility)
     */
    public function getGenderNameAttribute()
    {
        return $this->gender?->name ?? $this->gender;
    }

    /**
     * Get the user's custom ID
     */
    public function getCustomIdAttribute()
    {
        $prefix = $this->isStudent() ? 'STU' : ($this->isLibrarian() ? 'STF' : 'USR');
        return $prefix . '-' . str_pad($this->id, 6, '0', STR_PAD_LEFT);
    }
}
