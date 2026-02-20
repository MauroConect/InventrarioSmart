#!/bin/bash
# Script para arreglar permisos de storage

echo "🔧 Arreglando permisos de storage..."
echo ""

# Arreglar permisos dentro del contenedor
echo "📝 Estableciendo permisos en el contenedor..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -u root app sh -c "
    chown -R www:www /var/www/storage && \
    chown -R www:www /var/www/bootstrap/cache && \
    chmod -R 775 /var/www/storage && \
    chmod -R 775 /var/www/bootstrap/cache && \
    mkdir -p /var/www/storage/logs /var/www/storage/framework/sessions /var/www/storage/framework/views /var/www/storage/framework/cache && \
    touch /var/www/storage/logs/laravel.log && \
    chown www:www /var/www/storage/logs/laravel.log && \
    chmod 664 /var/www/storage/logs/laravel.log && \
    echo '✅ Permisos arreglados en el contenedor'
"

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Permisos arreglados correctamente"
    echo ""
    echo "💡 Si el problema persiste, también puedes arreglar los permisos en el host:"
    echo "   sudo chown -R \$USER:\$USER storage bootstrap/cache"
    echo "   chmod -R 775 storage bootstrap/cache"
else
    echo ""
    echo "❌ Error al arreglar permisos"
    exit 1
fi
