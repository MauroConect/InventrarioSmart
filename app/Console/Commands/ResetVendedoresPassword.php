<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ResetVendedoresPassword extends Command
{
    protected $signature = 'users:reset-vendedores-password
                            {--password=123456 : Contraseña nueva para todos los vendedores (por defecto 123456)}';

    protected $description = 'Asigna la misma contraseña a todos los usuarios con rol lógico vendedor (incluye alias: cajero, mostrador, etc.; no modifica admin).';

    public function handle(): int
    {
        $plain = (string) $this->option('password');
        if ($plain === '') {
            $this->error('La contraseña no puede estar vacía.');

            return self::FAILURE;
        }

        $hash = Hash::make($plain);
        $count = 0;

        foreach (User::query()->cursor() as $user) {
            if ($user->isAdmin()) {
                continue;
            }
            if ($user->logicalRoleKey() !== User::ROLE_VENDEDOR) {
                continue;
            }
            $user->forceFill(['password' => $hash])->save();
            $count++;
        }

        $this->info("Listo: {$count} usuario(s) con perfil vendedor. Nueva contraseña: {$plain}");

        return self::SUCCESS;
    }
}
