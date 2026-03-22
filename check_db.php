<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== USERS TABLE STRUCTURE ===\n";
echo str_repeat("=", 50) . "\n";

$columns = \Illuminate\Support\Facades\Schema::getColumnListing('users');
foreach ($columns as $column) {
    echo "- {$column}\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "=== ROLES TABLE ===\n";

$roles = \Illuminate\Support\Facades\DB::table('roles')->get();
foreach ($roles as $role) {
    echo "ID: {$role->id}, Name: {$role->name}\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "=== CURRENT USERS ===\n";

$users = \App\Models\User::with('role')->get();
foreach ($users as $user) {
    echo sprintf(
        "ID: %d | Name: %s | Email: %s | Library ID: %s | Role: %s\n",
        $user->id,
        $user->name ?? 'N/A',
        $user->email,
        $user->library_id ?? 'N/A',
        $user->role ? $user->role->name : 'No Role'
    );
}

echo "\n";
