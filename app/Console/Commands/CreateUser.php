<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateUser extends Command
{
    protected $signature = 'user:create
                            {name : Nombre del usuario}
                            {email : Email del usuario}
                            {password? : Password (opcional)}
                            {--role=vendedor : Rol del usuario (admin|vendedor)}';

    protected $description = 'Crea un usuario con rol admin o vendedor';

    public function handle(): int
    {
        $name = (string) $this->argument('name');
        $email = (string) $this->argument('email');
        $password = (string) ($this->argument('password') ?: Str::random(12));
        $role = (string) $this->option('role');

        if (!in_array($role, [User::ROLE_ADMIN, User::ROLE_VENDEDOR], true)) {
            $this->error('Rol invalido. Usa: admin o vendedor.');
            return self::FAILURE;
        }

        if (User::where('email', $email)->exists()) {
            $this->error('Ya existe un usuario con ese email.');
            return self::FAILURE;
        }

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => $role,
        ]);

        $this->info('Usuario creado correctamente.');
        $this->line("Nombre: {$name}");
        $this->line("Email: {$email}");
        $this->line("Rol: {$role}");
        $this->line("Password: {$password}");

        return self::SUCCESS;
    }
}
