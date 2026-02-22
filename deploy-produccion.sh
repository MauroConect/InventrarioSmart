#!/bin/bash

# Script de despliegue rápido para producción
# Uso: ./deploy-produccion.sh

set -e

echo "🚀 Desplegando Inventario Inteligente en modo PRODUCCIÓN..."

# Verificar Docker
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker no está corriendo"
    exit 1
fi

# Verificar .env
if [ ! -f .env ]; then
    echo "❌ Archivo .env no encontrado"
    exit 1
fi

# Detener y limpiar
echo "📦 Deteniendo contenedores..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml down

# Construir e iniciar
echo "🔨 Construyendo e iniciando servicios..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build

# Esperar a que esté listo
echo "⏳ Esperando a que los servicios estén listos..."
sleep 15

# Instalar dependencias
echo "📚 Instalando dependencias..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Permisos
echo "🔐 Configurando permisos..."
# Ejecutar como root usando docker exec directamente
CONTAINER_NAME=$(docker-compose -f docker-compose.yml -f docker-compose.prod.yml ps -q app)
if [ ! -z "$CONTAINER_NAME" ]; then
    docker exec -u root $CONTAINER_NAME sh -c "chmod -R 775 /var/www/storage /var/www/bootstrap/cache || true"
    docker exec -u root $CONTAINER_NAME sh -c "chown -R www:www /var/www/storage /var/www/bootstrap/cache || true"
fi

# Migraciones
echo "🗄️  Ejecutando migraciones..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan migrate --force

# Limpiar cachés
echo "🧹 Limpiando cachés..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan config:clear
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan cache:clear
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan route:clear
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan view:clear

# Optimizar
echo "⚡ Optimizando para producción..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan config:cache
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan route:cache
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan view:cache

echo ""
echo "✅ Despliegue completado!"
echo "🌐 Accede a: http://localhost:8000"
echo ""
