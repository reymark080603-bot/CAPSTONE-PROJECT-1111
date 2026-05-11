<?php
/**
 * Setup admin account for Railway deployment.
 * Run with: php setup_admin.php
 * Bypasses the User model 'hashed' cast by using DB directly.
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

// Read credentials from environment (with hardcoded fallbacks)
$email    = env('ADMIN_EMAIL',    'JHCSCLibrarian@gmail.com');
$password = env('ADMIN_PASSWORD', 'JHCSCLib2026');
$name     = env('ADMIN_NAME',     'Shienalie S. Lubon');

echo "[setup_admin] Email    : {$email}\n";
echo "[setup_admin] Name     : {$name}\n";
echo "[setup_admin] Password : (set)\n";

// Get the librarian role ID
$role = DB::table('roles')->where('name', 'librarian')->first();
if (!$role) {
    echo "[setup_admin] ERROR: 'librarian' role not found in roles table!\n";
    exit(1);
}
echo "[setup_admin] Role ID  : {$role->id}\n";

// Hash the password directly (NOT through the model cast to avoid double-hashing)
$hashedPassword = Hash::make($password);

// Check if user already exists
$existing = DB::table('users')->where('email', $email)->first();

if ($existing) {
    // Update existing user
    DB::table('users')->where('email', $email)->update([
        'name'               => $name,
        'firstname'          => 'Shienalie',
        'lastname'           => 'Lubon',
        'password'           => $hashedPassword,
        'role_id'            => $role->id,
        'email_verified_at'  => now(),
        'updated_at'         => now(),
    ]);
    echo "[setup_admin] ✅ Updated existing admin account (ID: {$existing->id})\n";
} else {
    // Create new user
    $id = DB::table('users')->insertGetId([
        'name'               => $name,
        'firstname'          => 'Shienalie',
        'lastname'           => 'Lubon',
        'email'              => $email,
        'password'           => $hashedPassword,
        'role_id'            => $role->id,
        'email_verified_at'  => now(),
        'created_at'         => now(),
        'updated_at'         => now(),
    ]);
    echo "[setup_admin] ✅ Created new admin account (ID: {$id})\n";
}

// Verify the hash works
$saved = DB::table('users')->where('email', $email)->first();
$hashOk = Hash::check($password, $saved->password);
echo "[setup_admin] Password verify: " . ($hashOk ? "✅ PASS" : "❌ FAIL") . "\n";

if (!$hashOk) {
    echo "[setup_admin] ERROR: Password hash verification failed!\n";
    exit(1);
}

echo "[setup_admin] Done. Login with: {$email} / {$password}\n";
