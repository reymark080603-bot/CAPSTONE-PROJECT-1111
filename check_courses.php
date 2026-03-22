<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== COURSES TABLE ===\n";
$courses = \Illuminate\Support\Facades\DB::table('courses')->get();
foreach ($courses as $course) {
    echo "ID: {$course->id}, Name: {$course->name}\n";
}

echo "\n=== USER COURSE DATA ===\n";
$users = \App\Models\User::whereHas('role', function($q) {
    $q->where('name', 'student');
})->limit(3)->get();

foreach ($users as $user) {
    echo "User: {$user->name}, course_id: {$user->course_id}\n";
}
