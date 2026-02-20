#!/bin/bash
# Script completo: instalar dependencias, arreglar red, verificar conexión

echo "🔧 Instalación y verificación completa..."
echo ""

# 1. Verificar que los contenedores estén corriendo
echo "📊 Paso 1/6: Verificando contenedores..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml ps
echo ""

# 2. Arreglar permisos
echo "📝 Paso 2/6: Arreglando permisos..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -u root app sh -c "
    chown -R www:www /var/www && \
    chmod -R 755 /var/www && \
    chmod -R 775 /var/www/storage /var/www/bootstrap/cache && \
    mkdir -p /var/www/storage/logs /var/www/storage/framework/sessions /var/www/storage/framework/views /var/www/storage/framework/cache && \
    touch /var/www/storage/logs/laravel.log && \
    chown www:www /var/www/storage/logs/laravel.log && \
    chmod 664 /var/www/storage/logs/laravel.log
" 2>/dev/null || echo "⚠️  Error al arreglar permisos (puede ser normal si ya están correctos)"
echo "✅ Permisos verificados"
echo ""

# 3. Instalar dependencias de Composer
echo "📦 Paso 3/6: Instalando dependencias de Composer..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

if [ $? -ne 0 ]; then
    echo "❌ Error al instalar dependencias"
    echo ""
    echo "💡 Intentando con permisos de root..."
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -u root -T app \
        composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
    
    if [ $? -ne 0 ]; then
        echo "❌ Error persistente al instalar dependencias"
        exit 1
    fi
    
    # Arreglar permisos después de instalar
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -u root app sh -c \
        "chown -R www:www /var/www/vendor"
fi

echo "✅ Dependencias instaladas"
echo ""

# 4. Verificar resolución DNS
echo "🔍 Paso 4/6: Verificando resolución DNS..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    ping -c 2 db > /dev/null 2>&1

if [ $? -eq 0 ]; then
    echo "✅ El contenedor app puede resolver 'db'"
else
    echo "❌ El contenedor app NO puede resolver 'db'"
    echo "   Reiniciando contenedores..."
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml restart app
    sleep 5
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
        ping -c 2 db > /dev/null 2>&1
    if [ $? -eq 0 ]; then
        echo "✅ Resolución DNS corregida después del reinicio"
    else
        echo "⚠️  Aún hay problemas con la resolución DNS"
    fi
fi
echo ""

# 5. Verificar configuración de DB
echo "📋 Paso 5/6: Verificando configuración de base de datos..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php -r "
    require '/var/www/vendor/autoload.php';
    \$app = require_once '/var/www/bootstrap/app.php';
    \$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    echo 'DB_HOST configurado: ' . config('database.connections.mysql.host') . '\n';
    echo 'DB_DATABASE: ' . config('database.connections.mysql.database') . '\n';
    echo 'DB_USERNAME: ' . config('database.connections.mysql.username') . '\n';
    "
echo ""

# 6. Probar conexión a la base de datos
echo "🔍 Paso 6/6: Probando conexión a la base de datos..."
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
        echo '   1. Verifica que DB_HOST=db en el archivo .env\n';
        echo '   2. Reinicia los contenedores: docker-compose restart\n';
        echo '   3. Verifica que el contenedor db esté corriendo\n';
        exit(1);
    }
    "

if [ $? -eq 0 ]; then
    echo ""
    echo "═══════════════════════════════════════════════════════════"
    echo "✅ ¡Todo configurado correctamente!"
    echo "═══════════════════════════════════════════════════════════"
    echo ""
    echo "💡 Ahora puedes crear el usuario con:"
    echo "   docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php -r \"require '/var/www/vendor/autoload.php'; \$app = require_once '/var/www/bootstrap/app.php'; \$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap(); \$user = \App\Models\User::where('email', 'admin@inventario.com')->first(); if (!\$user) { \App\Models\User::create(['name' => 'Administrador', 'email' => 'admin@inventario.com', 'password' => \Illuminate\Support\Facades\Hash::make('password123')]); echo 'Usuario creado\n'; } else { echo 'Usuario ya existe\n'; }\""
else
    echo ""
    echo "❌ Hay problemas con la conexión a la base de datos"
    echo "   Revisa los mensajes anteriores"
fi
