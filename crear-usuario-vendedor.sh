#!/bin/bash

# Script para crear o actualizar usuario vendedor
# Uso: ./crear-usuario-vendedor.sh [email] [password] [nombre]

set -e

echo "Creando/actualizando usuario vendedor..."

# Verificar que el contenedor está corriendo
if ! docker-compose -f docker-compose.yml -f docker-compose.prod.yml ps app | grep -q "Up"; then
    echo "El contenedor app no está corriendo. Ejecuta primero: ./deploy.sh"
    exit 1
fi

# Email, password y nombre por defecto
EMAIL=${1:-vendedor@inventario.com}
PASSWORD=${2:-password123}
NOMBRE=${3:-Vendedor}

echo "Email: $EMAIL"
echo "Password: $PASSWORD"
echo "Nombre: $NOMBRE"
echo ""

# Crear o actualizar usuario
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php -r "
require '/var/www/vendor/autoload.php';
\$app = require_once '/var/www/bootstrap/app.php';
\$kernel = \$app->make(\Illuminate\Contracts\Console\Kernel::class);
\$kernel->bootstrap();

\$email = '$EMAIL';
\$password = '$PASSWORD';
\$nombre = '$NOMBRE';

\$user = \App\Models\User::where('email', \$email)->first();

if (\$user) {
    \$user->name = \$nombre;
    \$user->password = \Illuminate\Support\Facades\Hash::make(\$password);
    \$user->role = 'vendedor';
    \$user->save();
    echo 'Usuario actualizado\n';
} else {
    \$user = \App\Models\User::create([
        'name' => \$nombre,
        'email' => \$email,
        'password' => \Illuminate\Support\Facades\Hash::make(\$password),
        'role' => 'vendedor'
    ]);
    echo 'Usuario creado\n';
}

echo 'Email: ' . \$email . '\n';
echo 'Password: ' . \$password . '\n';
echo 'Rol: vendedor\n';
"

echo ""
echo "Usuario vendedor listo para usar."
