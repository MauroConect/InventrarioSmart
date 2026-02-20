#!/bin/bash
# Script para solucionar problemas de red en Docker

echo "🔧 Solucionando problemas de red en Docker..."
echo ""

# 1. Detener todos los contenedores
echo "📦 Paso 1/4: Deteniendo contenedores..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml down
echo "✅ Contenedores detenidos"
echo ""

# 2. Eliminar red si existe (opcional, para recrearla)
echo "🌐 Paso 2/4: Verificando red..."
NETWORK_NAME=$(docker network ls | grep inventario | awk '{print $2}' | head -1)
if [ ! -z "$NETWORK_NAME" ]; then
    echo "   Red encontrada: $NETWORK_NAME"
    echo "   (No la eliminamos para conservar configuraciones)"
else
    echo "   Red no encontrada (se creará automáticamente)"
fi
echo ""

# 3. Levantar contenedores
echo "🚀 Paso 3/4: Levantando contenedores..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d
echo "✅ Contenedores levantados"
echo ""

# 4. Esperar a que la base de datos esté lista
echo "⏳ Paso 4/4: Esperando a que la base de datos esté lista..."
for i in {1..20}; do
    STATUS=$(docker inspect inventario_db --format='{{.State.Health.Status}}' 2>/dev/null || echo "starting")
    if [ "$STATUS" = "healthy" ]; then
        echo "   ✅ Base de datos lista (intento $i/20)"
        break
    fi
    if [ $i -eq 20 ]; then
        echo "   ⚠️  La base de datos aún no está lista, pero continuando..."
    else
        echo "   Intento $i/20... Estado: $STATUS"
        sleep 3
    fi
done
echo ""

# Verificar conexión
echo "🔍 Verificando resolución DNS..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    ping -c 2 db > /dev/null 2>&1

if [ $? -eq 0 ]; then
    echo "✅ El contenedor app puede resolver 'db'"
else
    echo "❌ El contenedor app NO puede resolver 'db'"
    echo ""
    echo "💡 Solución alternativa: Usar IP del contenedor"
    DB_IP=$(docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' inventario_db)
    if [ ! -z "$DB_IP" ]; then
        echo "   IP del contenedor db: $DB_IP"
        echo "   Puedes configurar DB_HOST=$DB_IP temporalmente"
    fi
fi
echo ""

# Verificar conexión a la base de datos desde la app
echo "🔍 Verificando conexión a la base de datos..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php -r "
    require '/var/www/vendor/autoload.php';
    \$app = require_once '/var/www/bootstrap/app.php';
    \$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    try {
        DB::connection()->getPdo();
        echo '✅ Conexión a la base de datos: EXITOSA\n';
        echo '   Host configurado: ' . config('database.connections.mysql.host') . '\n';
    } catch (\Exception \$e) {
        echo '❌ Error de conexión: ' . \$e->getMessage() . '\n';
        exit(1);
    }
    " 2>&1

echo ""
echo "✅ Proceso completado!"
