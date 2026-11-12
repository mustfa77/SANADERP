<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Creates the first Super Admin from the SUPER_ADMIN_* environment values.
 * An existing account with the same email is left untouched (only the role
 * is ensured).
 */
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $config = config('permissions.super_admin');

        if (blank($config['email']) || blank($config['password'])) {
            $this->command?->warn('SUPER_ADMIN_EMAIL / SUPER_ADMIN_PASSWORD are not set in .env; Super Admin not seeded.');

            return;
        }

        $user = User::query()->firstOrCreate(
            ['email' => $config['email']],
            [
                'name_ar' => $config['name_ar'],
                'name_en' => $config['name_en'],
                'phone' => $config['phone'],
                'password' => $config['password'],
                'status' => 'active',
                'user_type' => 'internal',
                'preferred_language' => 'ar',
                'email_verified_at' => now(),
            ],
        );

        $user->assignRole(config('permissions.super_admin_role'));
    }
}
