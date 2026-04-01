#!/bin/bash

# Crea o actualiza 3 usuarios vendedores: Brisa, Rosario, Cuca.
# Clave por defecto (cambiala en prod): ver comentario abajo o pasala como argumento.
#
# Uso:
#   ./crear-vendedores-brisa-rosario-cuca.sh
#   ./crear-vendedores-brisa-rosario-cuca.sh "TuClaveSegura123"
#
# Requiere contenedor Docker "app" arriba (mismo compose que crear-usuario-vendedor.sh).

set -e

export VENDEDOR_PASS="${1:-HeladoVend2026!}"

echo "Creando/actualizando vendedores Brisa, Rosario y Cuca..."
echo "Clave (la misma para los 3): $VENDEDOR_PASS"
echo ""

if ! docker-compose -f docker-compose.yml -f docker-compose.prod.yml ps app | grep -q "Up"; then
    echo "El contenedor app no está corriendo. Ejecuta primero: ./deploy.sh"
    exit 1
fi

docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T -e VENDEDOR_PASS="$VENDEDOR_PASS" app php -r "
require '/var/www/vendor/autoload.php';
\$app = require_once '/var/www/bootstrap/app.php';
\$kernel = \$app->make(\Illuminate\Contracts\Console\Kernel::class);
\$kernel->bootstrap();

\$password = getenv('VENDEDOR_PASS') ?: 'HeladoVend2026!';
\$hash = \Illuminate\Support\Facades\Hash::make(\$password);

\$usuarios = [
    ['name' => 'Brisa', 'email' => 'brisa@vendedores.local'],
    ['name' => 'Rosario', 'email' => 'rosario@vendedores.local'],
    ['name' => 'Cuca', 'email' => 'cuca@vendedores.local'],
];

foreach (\$usuarios as \$u) {
    \$user = \App\Models\User::where('email', \$u['email'])->first();
    if (\$user) {
        \$user->name = \$u['name'];
        \$user->password = \$hash;
        \$user->role = 'vendedor';
        \$user->save();
        echo 'Actualizado: ' . \$u['name'] . ' <' . \$u['email'] . '>' . PHP_EOL;
    } else {
        \App\Models\User::create([
            'name' => \$u['name'],
            'email' => \$u['email'],
            'password' => \$hash,
            'role' => 'vendedor',
        ]);
        echo 'Creado: ' . \$u['name'] . ' <' . \$u['email'] . '>' . PHP_EOL;
    }
}

echo PHP_EOL . 'Listo. Rol: vendedor' . PHP_EOL;
"

echo ""
echo "Hecho."
