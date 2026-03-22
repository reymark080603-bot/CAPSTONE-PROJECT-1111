<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create courses table
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->unique()->nullable();
            $table->string('department')->nullable();
            $table->timestamps();
        });

        // Create year_levels table
        Schema::create('year_levels', function (Blueprint $table) {
            $table->id();
            $table->string('level')->unique(); // '1st Year', '2nd Year', etc.
            $table->integer('numeric_level')->unique(); // 1, 2, 3, 4
            $table->timestamps();
        });

        // Create roles table
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // 'student', 'staff', 'admin'
            $table->string('display_name')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Create genders table
        Schema::create('genders', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // 'Male', 'Female', 'Other'
            $table->string('abbreviation')->unique()->nullable(); // 'M', 'F', 'O'
            $table->timestamps();
        });

        // Insert default data
        $this->insertDefaultData();
    }

    public function down(): void
    {
        Schema::dropIfExists('genders');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('year_levels');
        Schema::dropIfExists('courses');
    }

    private function insertDefaultData(): void
    {
        // Insert default year levels
        $yearLevels = [
            ['level' => '1st Year', 'numeric_level' => 1],
            ['level' => '2nd Year', 'numeric_level' => 2],
            ['level' => '3rd Year', 'numeric_level' => 3],
            ['level' => '4th Year', 'numeric_level' => 4],
            ['level' => '5th Year', 'numeric_level' => 5],
        ];

        foreach ($yearLevels as $level) {
            \DB::table('year_levels')->insert([
                'level' => $level['level'],
                'numeric_level' => $level['numeric_level'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Insert default roles
        $roles = [
            ['name' => 'student', 'display_name' => 'Student', 'description' => 'Regular student user'],
            ['name' => 'librarian', 'display_name' => 'Librarian', 'description' => 'Library staff with admin privileges'],
            ['name' => 'admin', 'display_name' => 'Administrator', 'description' => 'System administrator'],
        ];

        foreach ($roles as $role) {
            \DB::table('roles')->insert([
                'name' => $role['name'],
                'display_name' => $role['display_name'],
                'description' => $role['description'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Insert default genders
        $genders = [
            ['name' => 'Male', 'abbreviation' => 'M'],
            ['name' => 'Female', 'abbreviation' => 'F'],
            ['name' => 'Other', 'abbreviation' => 'O'],
        ];

        foreach ($genders as $gender) {
            \DB::table('genders')->insert([
                'name' => $gender['name'],
                'abbreviation' => $gender['abbreviation'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Insert common courses
        $courses = [
            ['name' => 'Computer Science', 'code' => 'CS'],
            ['name' => 'Information Technology', 'code' => 'IT'],
            ['name' => 'Engineering', 'code' => 'ENG'],
            ['name' => 'Business Administration', 'code' => 'BA'],
            ['name' => 'Accountancy', 'code' => 'ACC'],
            ['name' => 'Education', 'code' => 'ED'],
            ['name' => 'Nursing', 'code' => 'NUR'],
        ];

        foreach ($courses as $course) {
            \DB::table('courses')->insert([
                'name' => $course['name'],
                'code' => $course['code'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
