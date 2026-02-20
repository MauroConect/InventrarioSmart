#!/bin/bash
# Script completo para diagnosticar problemas de login

echo "🔍 DIAGNÓSTICO COMPLETO DE LOGIN"
echo "═══════════════════════════════════════════════════════════"
echo ""

# 1. Verificar conexión a la base de datos
echo "📊 Paso 1/7: Verificando conexión a la base de datos..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php -r "
    require '/var/www/vendor/autoload.php';
    \$app = require_once '/var/www/bootstrap/app.php';
    \$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    try {
        DB::connection()->getPdo();
        echo '✅ Conexión a la base de datos: OK\n';
    } catch (\Exception \$e) {
        echo '❌ Error de conexión: ' . \$e->getMessage() . '\n';
        exit(1);
    }
    "
echo ""

# 2. Verificar si el usuario existe
echo "👤 Paso 2/7: Verificando usuario en la base de datos..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php -r "
    require '/var/www/vendor/autoload.php';
    \$app = require_once '/var/www/bootstrap/app.php';
    \$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    \$user = \App\Models\User::where('email', 'admin@inventario.com')->first();
    if (\$user) {
        echo '✅ Usuario encontrado:\n';
        echo '   ID: ' . \$user->id . '\n';
        echo '   Nombre: ' . \$user->name . '\n';
        echo '   Email: ' . \$user->email . '\n';
        echo '   Creado: ' . \$user->created_at . '\n';
    } else {
        echo '❌ Usuario NO encontrado en la base de datos\n';
        echo '   Necesitas crear el usuario primero\n';
    }
    "
echo ""

# 3. Verificar que la contraseña funciona
echo "🔐 Paso 3/7: Verificando contraseña..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php -r "
    require '/var/www/vendor/autoload.php';
    \$app = require_once '/var/www/bootstrap/app.php';
    \$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    \$user = \App\Models\User::where('email', 'admin@inventario.com')->first();
    if (\$user) {
        if (\Illuminate\Support\Facades\Hash::check('password123', \$user->password)) {
            echo '✅ La contraseña \"password123\" es correcta\n';
        } else {
            echo '❌ La contraseña \"password123\" NO es correcta\n';
            echo '   Actualizando contraseña...\n';
            \$user->password = \Illuminate\Support\Facades\Hash::make('password123');
            \$user->save();
            echo '✅ Contraseña actualizada\n';
        }
    }
    "
echo ""

# 4. Verificar configuración de Sanctum
echo "🔑 Paso 4/7: Verificando configuración de Sanctum..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php -r "
    require '/var/www/vendor/autoload.php';
    \$app = require_once '/var/www/bootstrap/app.php';
    \$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    echo 'Sanctum configurado: ' . (class_exists('Laravel\Sanctum\Sanctum') ? '✅ Sí' : '❌ No') . '\n';
    "
echo ""

# 5. Probar el endpoint de login directamente
echo "🌐 Paso 5/7: Probando endpoint de login..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php -r "
    require '/var/www/vendor/autoload.php';
    \$app = require_once '/var/www/bootstrap/app.php';
    \$kernel = \$app->make(\Illuminate\Contracts\Console\Kernel::class);
    \$kernel->bootstrap();
    
    \$request = \Illuminate\Http\Request::create('/api/login', 'POST', [
        'email' => 'admin@inventario.com',
        'password' => 'password123'
    ]);
    \$request->headers->set('Content-Type', 'application/json');
    \$request->headers->set('Accept', 'application/json');
    
    try {
        \$response = \$app->handle(\$request);
        \$status = \$response->getStatusCode();
        \$content = \$response->getContent();
        
        if (\$status === 200) {
            echo '✅ Login exitoso (Status: ' . \$status . ')\n';
            echo 'Respuesta: ' . substr(\$content, 0, 200) . '\n';
        } else {
            echo '❌ Login falló (Status: ' . \$status . ')\n';
            echo 'Respuesta: ' . \$content . '\n';
        }
    } catch (\Exception \$e) {
        echo '❌ Error al probar login: ' . \$e->getMessage() . '\n';
    }
    "
echo ""

# 6. Verificar rutas API
echo "🛣️  Paso 6/7: Verificando rutas API..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan route:list --path=api/login 2>/dev/null || \
    echo "⚠️  No se pudo listar rutas (puede ser normal si hay caché)"
echo ""

# 7. Verificar logs recientes
echo "📋 Paso 7/7: Últimos errores en los logs..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    tail -n 20 /var/www/storage/logs/laravel.log 2>/dev/null || \
    echo "⚠️  No se pudieron leer los logs"
echo ""

echo "═══════════════════════════════════════════════════════════"
echo "✅ Diagnóstico completado"
echo "═══════════════════════════════════════════════════════════"
echo ""
echo "💡 Si el usuario no existe, ejecuta:"
echo "   docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php -r \"require '/var/www/vendor/autoload.php'; \$app = require_once '/var/www/bootstrap/app.php'; \$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap(); \App\Models\User::create(['name' => 'Administrador', 'email' => 'admin@inventario.com', 'password' => \Illuminate\Support\Facades\Hash::make('password123')]); echo 'Usuario creado\n';\""
echo ""
