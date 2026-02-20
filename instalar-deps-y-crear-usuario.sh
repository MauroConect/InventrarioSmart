#!/bin/bash
# Script para instalar dependencias y crear usuario

echo "🔧 Instalando dependencias y creando usuario..."
echo ""

# 1. Instalar dependencias de Composer
echo "📦 Paso 1/2: Instalando dependencias de Composer..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

if [ $? -ne 0 ]; then
    echo "❌ Error al instalar dependencias de Composer"
    echo ""
    echo "💡 Intenta ejecutar manualmente:"
    echo "   docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev"
    exit 1
fi

echo "✅ Dependencias instaladas"
echo ""

# 2. Crear usuario
echo "👤 Paso 2/2: Creando usuario administrador..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php -r "
    require '/var/www/vendor/autoload.php';
    \$app = require_once '/var/www/bootstrap/app.php';
    \$kernel = \$app->make(\Illuminate\Contracts\Console\Kernel::class);
    \$kernel->bootstrap();
    
    try {
        \$user = \App\Models\User::where('email', 'admin@inventario.com')->first();
        
        if (\$user) {
            echo '⚠️  El usuario admin@inventario.com ya existe\n';
            echo '   Actualizando contraseña...\n';
            \$user->password = \Illuminate\Support\Facades\Hash::make('password123');
            \$user->save();
            echo '✅ Contraseña actualizada\n';
        } else {
            echo '📝 Creando nuevo usuario...\n';
            \$user = \App\Models\User::create([
                'name' => 'Administrador',
                'email' => 'admin@inventario.com',
                'password' => \Illuminate\Support\Facades\Hash::make('password123')
            ]);
            echo '✅ Usuario creado exitosamente (ID: ' . \$user->id . ')\n';
        }
        
        // Verificar que la contraseña funciona
        if (\Illuminate\Support\Facades\Hash::check('password123', \$user->password)) {
            echo '✅ Verificación de contraseña exitosa\n';
        }
        
        echo '\n';
        echo '═══════════════════════════════════════════════════════════\n';
        echo '📋 Credenciales de acceso:\n';
        echo '   Email: admin@inventario.com\n';
        echo '   Contraseña: password123\n';
        echo '═══════════════════════════════════════════════════════════\n';
    } catch (\Exception \$e) {
        echo '❌ Error: ' . \$e->getMessage() . '\n';
        exit(1);
    }
    "

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Proceso completado exitosamente!"
else
    echo ""
    echo "❌ Error al crear usuario"
    exit 1
fi
