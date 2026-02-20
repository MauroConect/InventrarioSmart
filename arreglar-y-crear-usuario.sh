#!/bin/bash
# Script completo para arreglar permisos y crear usuario en producción

echo "🔧 Arreglando permisos y configurando usuario..."
echo ""

# 1. Arreglar permisos como root
echo "📝 Paso 1/5: Estableciendo permisos correctos..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -u root app sh -c "
    chown -R www:www /var/www && \
    chmod -R 755 /var/www && \
    chmod -R 775 /var/www/storage /var/www/bootstrap/cache && \
    mkdir -p /var/www/storage/logs /var/www/storage/framework/sessions /var/www/storage/framework/views /var/www/storage/framework/cache && \
    chown -R www:www /var/www/storage /var/www/bootstrap/cache
"

if [ $? -ne 0 ]; then
    echo "❌ Error al arreglar permisos"
    exit 1
fi

echo "✅ Permisos arreglados"
echo ""

# 2. Verificar dependencias de Composer
echo "📦 Paso 2/5: Verificando dependencias de Composer..."
if [ ! -d "vendor" ] || [ ! -f "vendor/autoload.php" ]; then
    echo "   Instalando dependencias..."
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
        composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
    
    if [ $? -ne 0 ]; then
        echo "❌ Error al instalar dependencias"
        exit 1
    fi
else
    echo "✅ Dependencias ya instaladas"
fi
echo ""

# 3. Generar clave de aplicación si no existe
echo "🔑 Paso 3/5: Verificando clave de aplicación..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan key:generate --force 2>/dev/null || echo "   Clave ya existe"
echo ""

# 4. Ejecutar migraciones
echo "🗄️  Paso 4/5: Ejecutando migraciones..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan migrate --force

if [ $? -ne 0 ]; then
    echo "❌ Error al ejecutar migraciones"
    exit 1
fi
echo ""

# 5. Crear o actualizar usuario
echo "👤 Paso 5/5: Creando/actualizando usuario administrador..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php -r "
    require '/var/www/vendor/autoload.php';
    \$app = require_once '/var/www/bootstrap/app.php';
    \$kernel = \$app->make(\Illuminate\Contracts\Console\Kernel::class);
    \$kernel->bootstrap();
    
    \$user = \App\Models\User::where('email', 'admin@inventario.com')->first();
    
    if (\$user) {
        echo '   Usuario encontrado, actualizando contraseña...\n';
        \$user->password = \Illuminate\Support\Facades\Hash::make('password123');
        \$user->save();
        echo '   ✅ Contraseña actualizada\n';
    } else {
        echo '   Creando nuevo usuario...\n';
        \$user = \App\Models\User::create([
            'name' => 'Administrador',
            'email' => 'admin@inventario.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password123')
        ]);
        echo '   ✅ Usuario creado\n';
    }
    
    // Verificar que la contraseña funciona
    if (\Illuminate\Support\Facades\Hash::check('password123', \$user->password)) {
        echo '   ✅ Verificación de contraseña exitosa\n';
    } else {
        echo '   ⚠️  Advertencia: La contraseña no se verifica correctamente\n';
    }
    "

echo ""
echo "═══════════════════════════════════════════════════════════"
echo "✅ Proceso completado exitosamente!"
echo "═══════════════════════════════════════════════════════════"
echo ""
echo "📋 Credenciales de acceso:"
echo "   Email: admin@inventario.com"
echo "   Contraseña: password123"
echo ""
echo "⚠️  IMPORTANTE: Cambia la contraseña después del primer inicio de sesión"
echo ""
