#!/bin/bash
# Script para corregir la configuración de DB_HOST

echo "🔧 Corrigiendo configuración de base de datos..."
echo ""

# Verificar configuración actual
echo "📋 Configuración actual de DB:"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php -r "
    require '/var/www/vendor/autoload.php';
    \$app = require_once '/var/www/bootstrap/app.php';
    \$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    echo 'DB_HOST: ' . env('DB_HOST', 'NO CONFIGURADO') . '\n';
    echo 'DB_PORT: ' . env('DB_PORT', 'NO CONFIGURADO') . '\n';
    echo 'DB_DATABASE: ' . env('DB_DATABASE', 'NO CONFIGURADO') . '\n';
    echo 'DB_USERNAME: ' . env('DB_USERNAME', 'NO CONFIGURADO') . '\n';
    "
echo ""

# Verificar si existe archivo .env
if [ -f .env ]; then
    echo "📝 Verificando archivo .env..."
    
    # Verificar DB_HOST en .env
    if grep -q "DB_HOST=127.0.0.1" .env || grep -q "DB_HOST=localhost" .env; then
        echo "⚠️  DB_HOST está configurado incorrectamente en .env"
        echo "   Corrigiendo DB_HOST a 'db'..."
        
        # Corregir DB_HOST
        sed -i 's/^DB_HOST=.*/DB_HOST=db/' .env
        sed -i 's/^DB_HOST=127\.0\.0\.1/DB_HOST=db/' .env
        sed -i 's/^DB_HOST=localhost/DB_HOST=db/' .env
        
        echo "✅ DB_HOST corregido en .env"
    else
        if ! grep -q "^DB_HOST=" .env; then
            echo "⚠️  DB_HOST no está configurado en .env"
            echo "   Agregando DB_HOST=db..."
            echo "DB_HOST=db" >> .env
            echo "✅ DB_HOST agregado a .env"
        else
            echo "✅ DB_HOST ya está configurado correctamente"
        fi
    fi
    
    # Verificar otras configuraciones
    if ! grep -q "^DB_PORT=" .env; then
        echo "DB_PORT=3306" >> .env
    fi
    
    if ! grep -q "^DB_DATABASE=" .env; then
        echo "DB_DATABASE=inventario_db" >> .env
    fi
    
    if ! grep -q "^DB_USERNAME=" .env; then
        echo "DB_USERNAME=inventario_user" >> .env
    fi
    
    if ! grep -q "^DB_PASSWORD=" .env; then
        echo "DB_PASSWORD=root" >> .env
    fi
    
    echo ""
    echo "📋 Configuración actualizada en .env:"
    grep "^DB_" .env | head -5
else
    echo "⚠️  Archivo .env no encontrado"
    echo "   Creando .env desde .env.example..."
    if [ -f .env.example ]; then
        cp .env.example .env
        # Asegurar que DB_HOST sea 'db'
        sed -i 's/^DB_HOST=.*/DB_HOST=db/' .env
        echo "✅ Archivo .env creado"
    else
        echo "❌ No se encontró .env.example"
        exit 1
    fi
fi

echo ""

# Limpiar caché de configuración
echo "🧹 Limpiando caché de configuración..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan config:clear 2>/dev/null || true

echo ""

# Verificar conexión
echo "🔍 Verificando conexión a la base de datos..."
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
        echo '\n💡 Verifica que:\n';
        echo '   1. El contenedor de la base de datos esté corriendo\n';
        echo '   2. DB_HOST esté configurado como \"db\" (no 127.0.0.1)\n';
        exit(1);
    }
    "

echo ""
echo "✅ Proceso completado!"
