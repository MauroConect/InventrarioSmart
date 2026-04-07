<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class AssignVendedoresPasswordsOneToEight extends Command
{
    protected $signature = 'users:assign-vendedores-passwords-1-8';

    protected $description = 'Asigna contraseñas "1" a "8" en orden a los usuarios vendedor (por id): 1º→1, 2º→2, … 8º→8, 9º→1 de nuevo. No modifica admin.';

    public function handle(): int
    {
        $vendedores = User::query()
            ->orderBy('id')
            ->get()
            ->filter(function (User $user) {
                if ($user->isAdmin()) {
                    return false;
                }

                return $user->logicalRoleKey() === User::ROLE_VENDEDOR;
            })
            ->values();

        if ($vendedores->isEmpty()) {
            $this->warn('No hay usuarios con perfil vendedor.');

            return self::SUCCESS;
        }

        $n = 0;
        foreach ($vendedores as $index => $user) {
            $digit = (string) (($index % 8) + 1);
            $user->forceFill(['password' => Hash::make($digit)])->save();
            $this->line("  #{$user->id} {$user->name} ({$user->email}) → contraseña: {$digit}");
            $n++;
        }

        $this->info("Listo: {$n} usuario(s) actualizado(s) (ciclo 1–8).");

        return self::SUCCESS;
    }
}
