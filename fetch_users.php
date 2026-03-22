<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== ALL USERS IN DATABASE ===\n";
echo str_repeat("=", 80) . "\n";

$users = \App\Models\User::with('role')->get();

foreach ($users as $user) {
    echo sprintf(
        "ID: %-3d | Name: %-20s | Email: %-25s | Library ID: %-10s | Role: %-10s | Verified: %-3s | Created: %s\n",
        $user->id,
        substr($user->firstname . ' ' . $user->lastname, 0, 20),
        substr($user->email, 0, 25),
        $user->library_id ?? 'N/A',
        $user->role ? $user->role->name : 'No Role',
        $user->email_verified_at ? 'Yes' : 'No',
        $user->created_at->format('Y-m-d')
    );
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "=== ROLE COUNTS ===\n";

$studentCount = \App\Models\User::whereHas('role', function($q) {
    $q->where('name', 'student');
})->count();

$librarianCount = \App\Models\User::whereHas('role', function($q) {
    $q->where('name', 'librarian');
})->count();

$adminCount = \App\Models\User::whereHas('role', function($q) {
    $q->where('name', 'admin');
})->count();

echo "Students:   {$studentCount}\n";
echo "Librarians: {$librarianCount}\n";
echo "Admins:     {$adminCount}\n";
echo "Total:      " . ($studentCount + $librarianCount + $adminCount) . "\n";

echo "\n" . str_repeat("=", 80) . "\n";
echo "=== LOGIN CREDENTIALS ===\n";

foreach ($users as $user) {
    if ($user->role) {
        $loginType = $user->role->name === 'student' ? 'Library ID' : 'Email';
        $loginValue = $user->role->name === 'student' ? ($user->library_id ?? 'N/A') : $user->email;
        
        echo sprintf(
            "%-10s | Login: %-15s | Email: %-25s | Status: %s\n",
            strtoupper($user->role->name),
            $loginValue,
            $user->email,
            $user->email_verified_at ? 'ACTIVE' : 'INACTIVE'
        );
    }
}

echo "\n";
