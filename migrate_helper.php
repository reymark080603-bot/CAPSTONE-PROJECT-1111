<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== DATA MIGRATION HELPER ===\n";
echo str_repeat("=", 50) . "\n";

echo "Current database: SQLite\n";
echo "Tables available: 21\n\n";

echo "To migrate your actual data, you have several options:\n\n";

echo "1. If you have a MySQL database with data:\n";
echo "   - Update .env file with MySQL credentials\n";
echo "   - Run: php artisan migrate\n";
echo "   - Create migration script to copy data\n\n";

echo "2. If you have SQL dump files:\n";
echo "   - Place .sql file in project root\n";
echo "   - Run: sqlite3 database/database.sqlite < your_file.sql\n\n";

echo "3. If you have Excel/CSV files:\n";
echo "   - I can create import scripts for your data\n\n";

echo "4. If you want to switch to MySQL:\n";
echo "   - Make sure MySQL server is running on port 3308\n";
echo "   - Create database: final_project1.0\n";
echo "   - Update .env file\n\n";

echo "Please tell me:\n";
echo "- Where is your current data stored?\n";
echo "- What format is it in (MySQL, CSV, Excel, etc.)?\n";
echo "- Do you want to use SQLite or MySQL?\n\n";

echo "Current users in database:\n";
$users = \App\Models\User::with('role')->get();
foreach ($users as $user) {
    echo "- {$user->name} ({$user->role->name})\n";
}

echo "\n";
