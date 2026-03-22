<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // First, add new foreign key columns to users table if they don't exist
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'course_id')) {
                $table->foreignId('course_id')->nullable()->after('library_id')->constrained('courses');
            }
            if (!Schema::hasColumn('users', 'year_level_id')) {
                $table->foreignId('year_level_id')->nullable()->after('course_id')->constrained('year_levels');
            }
            if (!Schema::hasColumn('users', 'role_id')) {
                $table->foreignId('role_id')->nullable()->after('year_level_id')->constrained('roles');
            }
            if (!Schema::hasColumn('users', 'gender_id')) {
                $table->foreignId('gender_id')->nullable()->after('gender')->constrained('genders');
            }
        });

        // Migrate existing data to normalized structure
        $this->migrateLegacyUserData();

        // Update the User model to use new relationships
        // This will be done in the model update step

        // Finally, drop the old columns after successful migration
        Schema::table('users', function (Blueprint $table) {
            $columnsToDrop = ['course', 'year', 'role'];
            
            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        // We'll keep the 'name' field for now as it might be used elsewhere
        // but we'll make sure firstname, mi, lastname are properly set
    }

    public function down(): void
    {
        // Add back the old columns for rollback
        Schema::table('users', function (Blueprint $table) {
            $table->string('course')->nullable()->after('course_id');
            $table->string('year')->nullable()->after('year_level_id');
            $table->string('role')->nullable()->after('role_id');
        });

        // Migrate data back to old format
        $this->rollbackUserData();

        // Drop foreign key columns
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
            $table->dropForeign(['year_level_id']);
            $table->dropForeign(['role_id']);
            $table->dropForeign(['gender_id']);
            $table->dropColumn(['course_id', 'year_level_id', 'role_id', 'gender_id']);
        });
    }

    private function migrateLegacyUserData(): void
    {
        // Migrate courses
        DB::table('users')->whereNotNull('course')->where('course', '!=', '')->orderBy('id')->chunk(100, function ($users) {
            foreach ($users as $user) {
                $course = DB::table('courses')->where('name', $user->course)->first();
                if (!$course) {
                    // Create new course if it doesn't exist
                    $courseId = DB::table('courses')->insertGetId([
                        'name' => $user->course,
                        'code' => strtoupper(substr($user->course, 0, 3)),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                } else {
                    $courseId = $course->id;
                }
                
                DB::table('users')->where('id', $user->id)->update([
                    'course_id' => $courseId
                ]);
            }
        });

        // Migrate year levels
        DB::table('users')->whereNotNull('year')->where('year', '!=', '')->orderBy('id')->chunk(100, function ($users) {
            foreach ($users as $user) {
                $yearLevel = DB::table('year_levels')->where('numeric_level', $user->year)->first();
                if (!$yearLevel) {
                    // Try to match by string
                    $yearLevel = DB::table('year_levels')->where('level', 'LIKE', "%{$user->year}%")->first();
                }
                
                if ($yearLevel) {
                    DB::table('users')->where('id', $user->id)->update([
                        'year_level_id' => $yearLevel->id
                    ]);
                }
            }
        });

        // Migrate roles
        DB::table('users')->whereNotNull('role')->where('role', '!=', '')->orderBy('id')->chunk(100, function ($users) {
            foreach ($users as $user) {
                $role = DB::table('roles')->where('name', $user->role)->first();
                if (!$role) {
                    // Create new role if it doesn't exist
                    $roleId = DB::table('roles')->insertGetId([
                        'name' => $user->role,
                        'display_name' => ucfirst($user->role),
                        'description' => 'Custom role created during migration',
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                } else {
                    $roleId = $role->id;
                }
                
                DB::table('users')->where('id', $user->id)->update([
                    'role_id' => $roleId
                ]);
            }
        });

        // Migrate genders
        DB::table('users')->whereNotNull('gender')->where('gender', '!=', '')->orderBy('id')->chunk(100, function ($users) {
            foreach ($users as $user) {
                $gender = DB::table('genders')->where('name', $user->gender)->first();
                if (!$gender) {
                    // Try to match by abbreviation
                    $gender = DB::table('genders')->where('abbreviation', $user->gender)->first();
                }
                
                if ($gender) {
                    DB::table('users')->where('id', $user->id)->update([
                        'gender_id' => $gender->id
                    ]);
                }
            }
        });

        // Ensure all users have a role_id (default to student if not set)
        DB::table('users')->whereNull('role_id')->update([
            'role_id' => DB::table('roles')->where('name', 'student')->value('id')
        ]);
    }

    private function rollbackUserData(): void
    {
        // Migrate course data back
        DB::table('users')->whereNotNull('course_id')->orderBy('id')->chunk(100, function ($users) {
            foreach ($users as $user) {
                $course = DB::table('courses')->where('id', $user->course_id)->first();
                if ($course) {
                    DB::table('users')->where('id', $user->id)->update([
                        'course' => $course->name
                    ]);
                }
            }
        });

        // Migrate year level data back
        DB::table('users')->whereNotNull('year_level_id')->orderBy('id')->chunk(100, function ($users) {
            foreach ($users as $user) {
                $yearLevel = DB::table('year_levels')->where('id', $user->year_level_id)->first();
                if ($yearLevel) {
                    DB::table('users')->where('id', $user->id)->update([
                        'year' => $yearLevel->numeric_level
                    ]);
                }
            }
        });

        // Migrate role data back
        DB::table('users')->whereNotNull('role_id')->orderBy('id')->chunk(100, function ($users) {
            foreach ($users as $user) {
                $role = DB::table('roles')->where('id', $user->role_id)->first();
                if ($role) {
                    DB::table('users')->where('id', $user->id)->update([
                        'role' => $role->name
                    ]);
                }
            }
        });

        // Migrate gender data back
        DB::table('users')->whereNotNull('gender_id')->orderBy('id')->chunk(100, function ($users) {
            foreach ($users as $user) {
                $gender = DB::table('genders')->where('id', $user->gender_id)->first();
                if ($gender) {
                    DB::table('users')->where('id', $user->id)->update([
                        'gender' => $gender->name
                    ]);
                }
            }
        });
    }
};
