#!/bin/bash
set -e

echo "=== Starting Knowly Application ==="

echo "→ Clearing config cache first (to ensure fresh env vars)..."
php artisan config:clear
php artisan cache:clear

echo "→ Running database migrations..."
php artisan migrate --force

echo "→ Setting up admin/librarian account..."
ADMIN_EMAIL_VAL="${ADMIN_EMAIL:-JHCSCLibrarian@gmail.com}"
ADMIN_PASSWORD_VAL="${ADMIN_PASSWORD:-JHCSCLib2026}"
ADMIN_NAME_VAL="${ADMIN_NAME:-Shienalie S. Lubon}"

echo "   Email: $ADMIN_EMAIL_VAL"
echo "   Name:  $ADMIN_NAME_VAL"

php artisan tinker --execute="
    use App\Models\User;
    use App\Models\Role;
    use Illuminate\Support\Facades\Hash;

    \$role = Role::where('name', 'librarian')->first();
    if (!\$role) { echo 'ERROR: librarian role not found'; exit(1); }

    \$user = User::updateOrCreate(
        ['email' => '$ADMIN_EMAIL_VAL'],
        [
            'name'              => '$ADMIN_NAME_VAL',
            'firstname'         => 'Shienalie',
            'lastname'          => 'Lubon',
            'password'          => Hash::make('$ADMIN_PASSWORD_VAL'),
            'role_id'           => \$role->id,
            'email_verified_at' => now(),
        ]
    );
    echo 'Admin account OK: ' . \$user->email . ' (ID ' . \$user->id . ')';
"

echo "→ Creating storage symlink..."
php artisan storage:link --force 2>/dev/null || true

echo "=== Starting PHP server on port ${PORT:-8000} ==="
php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
