#!/bin/bash

# Script para arreglar permisos en el contenedor

echo "🔧 Arreglando permisos en el contenedor..."
echo ""

# Arreglar permisos como root
echo "📝 Estableciendo permisos correctos..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -u root app sh -c "
    chown -R www:www /var/www && \
    chmod -R 755 /var/www && \
    chmod -R 775 /var/www/storage /var/www/bootstrap/cache && \
    mkdir -p /var/www/vendor && \
    chown -R www:www /var/www/vendor
"

if [ $? -eq 0 ]; then
    echo "✅ Permisos arreglados"
    echo ""
    echo "📦 Instalando dependencias de Composer..."
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
        composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
    
    if [ $? -eq 0 ]; then
        echo ""
        echo "✅ Dependencias instaladas correctamente"
    else
        echo ""
        echo "❌ Error al instalar dependencias"
        exit 1
    fi
else
    echo ""
    echo "❌ Error al arreglar permisos"
    exit 1
fi
