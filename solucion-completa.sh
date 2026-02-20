#!/bin/bash
# Solución completa para arreglar permisos, limpiar caché y crear usuario

echo "🔧 Solución completa para producción..."
echo ""

# 1. Arreglar permisos
echo "📝 Paso 1/6: Arreglando permisos..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -u root app sh -c "
    chown -R www:www /var/www && \
    chmod -R 755 /var/www && \
    chmod -R 775 /var/www/storage /var/www/bootstrap/cache && \
    mkdir -p /var/www/storage/logs /var/www/storage/framework/sessions /var/www/storage/framework/views /var/www/storage/framework/cache && \
    chown -R www:www /var/www/storage /var/www/bootstrap/cache && \
    touch /var/www/storage/logs/laravel.log && \
    chown www:www /var/www/storage/logs/laravel.log && \
    chmod 664 /var/www/storage/logs/laravel.log
"

if [ $? -ne 0 ]; then
    echo "❌ Error al arreglar permisos"
    exit 1
fi
echo "✅ Permisos arreglados"
echo ""

# 2. Limpiar todas las cachés
echo "🧹 Paso 2/6: Limpiando cachés de Laravel..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan config:clear 2>/dev/null || true

docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan cache:clear 2>/dev/null || true

docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan route:clear 2>/dev/null || true

docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan view:clear 2>/dev/null || true

echo "✅ Cachés limpiadas"
echo ""

# 3. Verificar/instalar dependencias
echo "📦 Paso 3/6: Verificando dependencias de Composer..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

if [ $? -ne 0 ]; then
    echo "❌ Error al instalar dependencias"
    exit 1
fi
echo "✅ Dependencias verificadas"
echo ""

# 4. Generar clave de aplicación
echo "🔑 Paso 4/6: Generando clave de aplicación..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan key:generate --force 2>/dev/null || echo "   Clave ya existe"
echo ""

# 5. Ejecutar migraciones
echo "🗄️  Paso 5/6: Ejecutando migraciones..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan migrate --force

if [ $? -ne 0 ]; then
    echo "❌ Error al ejecutar migraciones"
    exit 1
fi
echo "✅ Migraciones ejecutadas"
echo ""

# 6. Crear o actualizar usuario (usando PHP directo para evitar problemas de tinker)
echo "👤 Paso 6/6: Creando/actualizando usuario administrador..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php -r "
    require '/var/www/vendor/autoload.php';
    \$app = require_once '/var/www/bootstrap/app.php';
    \$kernel = \$app->make(\Illuminate\Contracts\Console\Kernel::class);
    \$kernel->bootstrap();
    
    try {
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
            echo '   ✅ Usuario creado (ID: ' . \$user->id . ')\n';
        }
        
        // Verificar que la contraseña funciona
        if (\Illuminate\Support\Facades\Hash::check('password123', \$user->password)) {
            echo '   ✅ Verificación de contraseña exitosa\n';
        } else {
            echo '   ⚠️  Advertencia: La contraseña no se verifica correctamente\n';
        }
    } catch (\Exception \$e) {
        echo '   ❌ Error: ' . \$e->getMessage() . '\n';
        exit(1);
    }
    "

if [ $? -eq 0 ]; then
    echo ""
    echo "═══════════════════════════════════════════════════════════"
    echo "✅ ¡Proceso completado exitosamente!"
    echo "═══════════════════════════════════════════════════════════"
    echo ""
    echo "📋 Credenciales de acceso:"
    echo "   Email: admin@inventario.com"
    echo "   Contraseña: password123"
    echo ""
    echo "⚠️  IMPORTANTE: Cambia la contraseña después del primer inicio de sesión"
    echo ""
else
    echo ""
    echo "❌ Error al crear/actualizar usuario"
    exit 1
fi
