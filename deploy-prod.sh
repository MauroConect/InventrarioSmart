#!/bin/bash

# Script rápido de despliegue en producción
# Uso: ./deploy-prod.sh

set -e

echo "🚀 Desplegando en PRODUCCIÓN..."
echo ""

# Colores
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Verificar Docker
if ! command -v docker &> /dev/null; then
    echo -e "${RED}✗ Docker no está instalado${NC}"
    exit 1
fi

# Verificar .env
if [ ! -f .env ]; then
    echo -e "${YELLOW}⚠ Archivo .env no encontrado${NC}"
    if [ -f .env.example ]; then
        cp .env.example .env
        echo -e "${GREEN}✓ Creado .env desde .env.example${NC}"
        echo -e "${YELLOW}⚠ IMPORTANTE: Edita .env con tus configuraciones de producción${NC}"
        echo "Presiona Enter para continuar o Ctrl+C para cancelar..."
        read
    else
        echo -e "${RED}✗ No se encontró .env.example${NC}"
        exit 1
    fi
fi

# Verificar que APP_ENV=production
if ! grep -q "APP_ENV=production" .env; then
    echo -e "${YELLOW}⚠ ADVERTENCIA: APP_ENV no está configurado como 'production' en .env${NC}"
    echo "¿Deseas continuar de todos modos? (s/n)"
    read -r respuesta
    if [ "$respuesta" != "s" ] && [ "$respuesta" != "S" ]; then
        echo "Cancelado."
        exit 1
    fi
fi

echo ""
echo "📦 Paso 1/7: Deteniendo contenedores existentes..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml down 2>/dev/null || true

echo ""
echo "🔨 Paso 2/7: Construyendo imágenes (esto puede tardar varios minutos)..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml build --no-cache

echo ""
echo "🚀 Paso 3/7: Levantando contenedores..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d

echo ""
echo "⏳ Paso 4/7: Esperando a que la base de datos esté lista..."
sleep 20

echo ""
echo "📚 Paso 5/7: Instalando dependencias de PHP..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

echo ""
echo "🔑 Paso 6/7: Configurando Laravel..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan key:generate --force 2>/dev/null || true

docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan migrate --force

docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan storage:link || true

echo ""
echo "⚡ Paso 7/7: Optimizando para producción..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan config:cache

docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan route:cache

docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan view:cache

docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan event:cache

echo ""
echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}  ✓ DESPLIEGUE COMPLETADO EXITOSAMENTE${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
echo ""
echo "📊 Estado de los contenedores:"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml ps
echo ""
echo "📝 Comandos útiles:"
echo "  Ver logs:        docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs -f"
echo "  Ver recursos:    docker stats"
echo "  Detener:         docker-compose -f docker-compose.yml -f docker-compose.prod.yml down"
echo "  Reiniciar:       docker-compose -f docker-compose.yml -f docker-compose.prod.yml restart"
echo ""
echo "🌐 Aplicación disponible en: http://localhost (puerto 80)"
echo ""
