#!/bin/bash
# Script para verificar la conexión a la base de datos desde el contenedor app

echo "🔍 VERIFICACIÓN DE CONEXIÓN A LA BASE DE DATOS"
echo "═══════════════════════════════════════════════════════════"
echo ""

# 1. Verificar que los contenedores están en la misma red
echo "📊 Paso 1/6: Verificando red de los contenedores..."
echo ""

echo "Red del contenedor app:"
docker inspect inventario_app --format='{{range $key, $value := .NetworkSettings.Networks}}{{$key}}{{end}}' 2>/dev/null || echo "No encontrado"
echo ""

echo "Red del contenedor db:"
docker inspect inventario_db --format='{{range $key, $value := .NetworkSettings.Networks}}{{$key}}{{end}}' 2>/dev/null || echo "No encontrado"
echo ""

# 2. Obtener IPs de los contenedores
echo "🌐 Paso 2/6: Direcciones IP de los contenedores..."
echo ""

APP_IP=$(docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' inventario_app 2>/dev/null)
DB_IP=$(docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' inventario_db 2>/dev/null)

echo "IP del contenedor app: $APP_IP"
echo "IP del contenedor db: $DB_IP"
echo ""

# 3. Verificar resolución DNS
echo "🔍 Paso 3/6: Verificando resolución DNS..."
echo ""

echo "Probando ping desde app a db:"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    ping -c 2 db 2>&1 | head -5

echo ""
echo "Probando nslookup desde app:"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    nslookup db 2>&1 | head -10 || echo "nslookup no disponible, probando con getent..."
    
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    getent hosts db 2>&1 || echo "getent no disponible"
echo ""

# 4. Verificar configuración de .env
echo "📋 Paso 4/6: Verificando configuración de DB_HOST..."
echo ""

if [ -f .env ]; then
    echo "Configuración en .env:"
    grep "^DB_HOST=" .env || echo "DB_HOST no encontrado en .env"
    grep "^DB_DATABASE=" .env || echo "DB_DATABASE no encontrado en .env"
    grep "^DB_USERNAME=" .env || echo "DB_USERNAME no encontrado en .env"
    grep "^DB_PASSWORD=" .env || echo "DB_PASSWORD no encontrado en .env"
else
    echo "⚠️  Archivo .env no encontrado"
fi
echo ""

# 5. Verificar variables de entorno en docker-compose
echo "🐳 Paso 5/6: Variables de entorno en docker-compose.yml..."
echo ""

echo "Variables DB en docker-compose.yml:"
grep -A 5 "environment:" docker-compose.yml | grep "DB_" || echo "No encontradas"
echo ""

# 6. Probar conexión directa a MySQL desde el contenedor app
echo "🔌 Paso 6/6: Probando conexión directa a MySQL..."
echo ""

echo "Opción 1: Usando el nombre 'db'"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    sh -c "timeout 5 mysql -h db -u inventario_user -proot inventario_db -e 'SELECT 1;' 2>&1" || \
    echo "❌ No se pudo conectar usando 'db'"
echo ""

if [ ! -z "$DB_IP" ]; then
    echo "Opción 2: Usando la IP directa ($DB_IP)"
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
        sh -c "timeout 5 mysql -h $DB_IP -u inventario_user -proot inventario_db -e 'SELECT 1;' 2>&1" || \
        echo "❌ No se pudo conectar usando IP"
    echo ""
fi

# 7. Verificar desde Laravel (si las dependencias están instaladas)
echo "📦 Verificando desde Laravel (si las dependencias están instaladas)..."
echo ""

if docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app test -f /var/www/vendor/autoload.php; then
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
        php -r "
        require '/var/www/vendor/autoload.php';
        \$app = require_once '/var/www/bootstrap/app.php';
        \$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        echo 'DB_HOST configurado: ' . config('database.connections.mysql.host') . '\n';
        try {
            DB::connection()->getPdo();
            echo '✅ Conexión desde Laravel: EXITOSA\n';
        } catch (\Exception \$e) {
            echo '❌ Error desde Laravel: ' . \$e->getMessage() . '\n';
        }
        " 2>&1
else
    echo "⚠️  Dependencias de Composer no instaladas aún"
    echo "   Instala primero: docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev"
fi

echo ""
echo "═══════════════════════════════════════════════════════════"
echo "✅ Verificación completada"
echo "═══════════════════════════════════════════════════════════"
echo ""
echo "💡 Soluciones según el problema:"
echo ""
echo "1. Si 'db' no se resuelve:"
echo "   docker-compose -f docker-compose.yml -f docker-compose.prod.yml restart app"
echo ""
echo "2. Si la conexión falla, usa la IP temporalmente:"
if [ ! -z "$DB_IP" ]; then
    echo "   sed -i 's/^DB_HOST=.*/DB_HOST=$DB_IP/' .env"
    echo "   docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan config:clear"
fi
echo ""
echo "3. Verificar que ambos contenedores estén en la misma red:"
echo "   docker network inspect inventariointeligente_inventario-network"
echo ""
