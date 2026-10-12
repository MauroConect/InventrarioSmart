<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class PlatformSuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('PLATFORM_SUPERADMIN_EMAIL');

        if (! $email) {
            $this->command?->warn('PLATFORM_SUPERADMIN_EMAIL no definido; se omite el superadmin.');

            return;
        }

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $password = env('PLATFORM_SUPERADMIN_PASSWORD');

            if (! $password) {
                throw new RuntimeException('PLATFORM_SUPERADMIN_PASSWORD es obligatorio para crear el superadmin.');
            }

            $user = User::query()->create([
                'name' => env('PLATFORM_SUPERADMIN_NAME', 'Platform Admin'),
                'email' => $email,
                'password' => $password,
                'role' => User::ROLE_ADMIN,
            ]);
        }

        $user->forceFill([
            'is_platform_superadmin' => true,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();
    }
}
