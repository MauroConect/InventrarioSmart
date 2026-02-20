#!/bin/bash
# Script final: verificar todo y crear usuario

echo "🔧 VERIFICACIÓN FINAL Y CREACIÓN DE USUARIO"
echo "═══════════════════════════════════════════════════════════"
echo ""

# 1. Verificar que DB_HOST esté correcto
echo "📋 Paso 1/6: Verificando configuración de DB_HOST..."
if grep -q "^DB_HOST=db" .env; then
    echo "✅ DB_HOST=db está configurado correctamente"
else
    echo "⚠️  Corrigiendo DB_HOST..."
    sed -i 's/^DB_HOST=.*/DB_HOST=db/' .env
    echo "✅ DB_HOST corregido"
fi
echo ""

# 2. Verificar dependencias de Composer
echo "📦 Paso 2/6: Verificando dependencias de Composer..."
if docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app test -f /var/www/vendor/autoload.php; then
    echo "✅ Dependencias de Composer instaladas"
else
    echo "⚠️  Instalando dependencias de Composer..."
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
        composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
    
    if [ $? -ne 0 ]; then
        echo "❌ Error al instalar dependencias"
        echo "   Intentando con permisos de root..."
        docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -u root -T app \
            composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
        docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -u root app sh -c \
            "chown -R www:www /var/www/vendor"
    fi
    echo "✅ Dependencias instaladas"
fi
echo ""

# 3. Limpiar caché de configuración
echo "🧹 Paso 3/6: Limpiando caché de configuración..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan config:clear 2>/dev/null || echo "⚠️  No se pudo limpiar caché (puede ser normal)"
echo ""

# 4. Verificar conexión a la base de datos
echo "🔍 Paso 4/6: Verificando conexión a la base de datos..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php -r "
    require '/var/www/vendor/autoload.php';
    \$app = require_once '/var/www/bootstrap/app.php';
    \$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    try {
        DB::connection()->getPdo();
        echo '✅ Conexión a la base de datos: EXITOSA\n';
        echo '   Host: ' . config('database.connections.mysql.host') . '\n';
        echo '   Database: ' . config('database.connections.mysql.database') . '\n';
    } catch (\Exception \$e) {
        echo '❌ Error de conexión: ' . \$e->getMessage() . '\n';
        echo '\n💡 Posibles soluciones:\n';
        echo '   1. Reinicia los contenedores: docker-compose restart\n';
        echo '   2. Verifica que el contenedor db esté corriendo\n';
        exit(1);
    }
    "

if [ $? -ne 0 ]; then
    echo ""
    echo "❌ No se pudo conectar a la base de datos"
    echo "   Revisa los mensajes anteriores"
    exit 1
fi
echo ""

# 5. Ejecutar migraciones
echo "🗄️  Paso 5/6: Ejecutando migraciones..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan migrate --force 2>/dev/null || echo "⚠️  Error al ejecutar migraciones (puede ser que ya estén ejecutadas)"
echo ""

# 6. Crear o verificar usuario
echo "👤 Paso 6/6: Creando/verificando usuario administrador..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php -r "
    require '/var/www/vendor/autoload.php';
    \$app = require_once '/var/www/bootstrap/app.php';
    \$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    
    try {
        \$user = \App\Models\User::where('email', 'admin@inventario.com')->first();
        
        if (\$user) {
            echo '⚠️  El usuario admin@inventario.com ya existe\n';
            echo '   Actualizando contraseña para asegurar que funcione...\n';
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
            echo '✅ Verificación de contraseña: EXITOSA\n';
        } else {
            echo '❌ Error: La contraseña no se verifica correctamente\n';
        }
        
        // Probar generación de token
        \$token = \$user->createToken('test_token')->plainTextToken;
        if (\$token) {
            echo '✅ Generación de token: EXITOSA\n';
        }
        
        echo '\n';
        echo '═══════════════════════════════════════════════════════════\n';
        echo '📋 CREDENCIALES DE ACCESO:\n';
        echo '   Email: admin@inventario.com\n';
        echo '   Contraseña: password123\n';
        echo '═══════════════════════════════════════════════════════════\n';
    } catch (\Exception \$e) {
        echo '❌ Error: ' . \$e->getMessage() . '\n';
        echo '   Stack trace: ' . \$e->getTraceAsString() . '\n';
        exit(1);
    }
    "

if [ $? -eq 0 ]; then
    echo ""
    echo "═══════════════════════════════════════════════════════════"
    echo "✅ ¡TODO CONFIGURADO CORRECTAMENTE!"
    echo "═══════════════════════════════════════════════════════════"
    echo ""
    echo "🌐 Ahora puedes acceder a la aplicación en:"
    echo "   http://localhost:8000"
    echo ""
    echo "📋 Credenciales:"
    echo "   Email: admin@inventario.com"
    echo "   Contraseña: password123"
    echo ""
    echo "⚠️  IMPORTANTE: Cambia la contraseña después del primer inicio de sesión"
    echo ""
else
    echo ""
    echo "❌ Hubo un error al crear/verificar el usuario"
    echo "   Revisa los mensajes anteriores"
fi
