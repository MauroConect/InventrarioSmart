#!/bin/bash
# Solución completa: permisos + migraciones

echo "🔧 Solucionando permisos y ejecutando migraciones..."
echo ""

# 1. Arreglar permisos en el contenedor
echo "📝 Paso 1/3: Arreglando permisos en el contenedor..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -u root app sh -c "
    chown -R www:www /var/www/storage && \
    chown -R www:www /var/www/bootstrap/cache && \
    chmod -R 775 /var/www/storage && \
    chmod -R 775 /var/www/bootstrap/cache && \
    mkdir -p /var/www/storage/logs /var/www/storage/framework/sessions /var/www/storage/framework/views /var/www/storage/framework/cache && \
    touch /var/www/storage/logs/laravel.log && \
    chown www:www /var/www/storage/logs/laravel.log && \
    chmod 664 /var/www/storage/logs/laravel.log && \
    echo '✅ Permisos arreglados'
"

if [ $? -ne 0 ]; then
    echo "❌ Error al arreglar permisos en el contenedor"
    echo "   Intentando arreglar permisos en el host..."
    
    # Arreglar permisos en el host también
    if [ -d "storage" ]; then
        echo "   Arreglando permisos en el host..."
        sudo chown -R $USER:$USER storage bootstrap/cache 2>/dev/null || chown -R $USER:$USER storage bootstrap/cache 2>/dev/null || true
        chmod -R 775 storage bootstrap/cache
        echo "   ✅ Permisos arreglados en el host"
    fi
fi
echo ""

# 2. Arreglar permisos en el host (si el volumen está montado)
echo "📝 Paso 2/3: Arreglando permisos en el host (si es necesario)..."
if [ -d "storage" ]; then
    # Intentar con sudo primero, luego sin sudo
    sudo chown -R $USER:$USER storage bootstrap/cache 2>/dev/null || \
    chown -R $USER:$USER storage bootstrap/cache 2>/dev/null || true
    
    chmod -R 775 storage bootstrap/cache 2>/dev/null || true
    
    # Crear directorios si no existen
    mkdir -p storage/logs storage/framework/sessions storage/framework/views storage/framework/cache 2>/dev/null || true
    touch storage/logs/laravel.log 2>/dev/null || true
    chmod 664 storage/logs/laravel.log 2>/dev/null || true
    
    echo "✅ Permisos arreglados en el host"
else
    echo "⚠️  Directorio storage no encontrado en el host (puede ser normal si está solo en el contenedor)"
fi
echo ""

# 3. Ejecutar migraciones
echo "🗄️  Paso 3/3: Ejecutando migraciones de base de datos..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan migrate --force

if [ $? -eq 0 ]; then
    echo "✅ Migraciones ejecutadas correctamente"
else
    echo "❌ Error al ejecutar migraciones"
    echo ""
    echo "💡 Intenta ejecutar manualmente:"
    echo "   docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan migrate --force"
    exit 1
fi

echo ""
echo "═══════════════════════════════════════════════════════════"
echo "✅ Proceso completado!"
echo "═══════════════════════════════════════════════════════════"
echo ""
echo "💡 Verifica que todo funcione:"
echo "   docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app ls -la /var/www/storage/logs"
echo "   docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan migrate:status"
