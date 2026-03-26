#!/bin/bash

# Script para crear o actualizar usuario administrador
# Uso: ./crear-usuario-admin.sh

set -e

echo "👤 Creando/actualizando usuario administrador..."

# Verificar que el contenedor está corriendo
if ! docker-compose -f docker-compose.yml -f docker-compose.prod.yml ps app | grep -q "Up"; then
    echo "❌ El contenedor app no está corriendo. Ejecuta primero: ./deploy.sh"
    exit 1
fi

# Email y password por defecto
EMAIL=${1:-admin@inventario.com}
PASSWORD=${2:-password123}

echo "📧 Email: $EMAIL"
echo "🔑 Password: $PASSWORD"
echo ""

# Crear o actualizar usuario
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php -r "
require '/var/www/vendor/autoload.php';
\$app = require_once '/var/www/bootstrap/app.php';
\$kernel = \$app->make(\Illuminate\Contracts\Console\Kernel::class);
\$kernel->bootstrap();

\$email = '$EMAIL';
\$password = '$PASSWORD';

\$user = \App\Models\User::where('email', \$email)->first();

if (\$user) {
    \$user->password = \Illuminate\Support\Facades\Hash::make(\$password);
    \$user->role = 'admin';
    \$user->save();
    echo '✅ Usuario actualizado\n';
} else {
    \$user = \App\Models\User::create([
        'name' => 'Administrador',
        'email' => \$email,
        'password' => \Illuminate\Support\Facades\Hash::make(\$password),
        'role' => 'admin'
    ]);
    echo '✅ Usuario creado\n';
}

echo 'Email: ' . \$email . '\n';
echo 'Password: ' . \$password . '\n';
"

echo ""
echo "✅ Usuario listo para usar!"
