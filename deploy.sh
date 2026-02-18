#!/bin/bash

# Script de despliegue automatizado para Inventario Inteligente
# Uso: ./deploy.sh [production|development]

set -e

ENVIRONMENT=${1:-development}
echo "🚀 Iniciando despliegue en modo: $ENVIRONMENT"

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Función para imprimir mensajes
print_message() {
    echo -e "${GREEN}✓${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

# Verificar que Docker esté instalado
if ! command -v docker &> /dev/null; then
    print_error "Docker no está instalado. Por favor instala Docker primero."
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    print_error "Docker Compose no está instalado. Por favor instala Docker Compose primero."
    exit 1
fi

print_message "Docker y Docker Compose están instalados"

# Verificar si existe .env
if [ ! -f .env ]; then
    print_warning "Archivo .env no encontrado. Creando desde .env.example..."
    if [ -f .env.example ]; then
        cp .env.example .env
        print_message "Archivo .env creado desde .env.example"
        print_warning "IMPORTANTE: Edita el archivo .env con tus configuraciones antes de continuar"
        read -p "Presiona Enter cuando hayas editado el .env..."
    else
        print_error "No se encontró .env.example. Por favor crea un archivo .env manualmente."
        exit 1
    fi
fi

# Detener contenedores existentes
print_message "Deteniendo contenedores existentes..."
if [ "$ENVIRONMENT" = "production" ]; then
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml down
else
    docker-compose -f docker-compose.dev.yml down
fi

# Construir y levantar contenedores
print_message "Construyendo y levantando contenedores Docker..."
if [ "$ENVIRONMENT" = "production" ]; then
    print_message "Usando configuración de producción (assets se compilan en el build)..."
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
else
    print_message "Usando configuración de desarrollo..."
    docker-compose -f docker-compose.dev.yml up -d --build
fi

# Esperar a que la base de datos esté lista
print_message "Esperando a que la base de datos esté lista..."
sleep 15

# Instalar dependencias de Composer
print_message "Instalando dependencias de PHP (Composer)..."
if [ "$ENVIRONMENT" = "production" ]; then
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
else
    docker-compose -f docker-compose.dev.yml exec -T app composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Generar clave de aplicación si no existe
if [ "$ENVIRONMENT" = "production" ]; then
    COMPOSE_CMD="docker-compose -f docker-compose.yml -f docker-compose.prod.yml"
else
    COMPOSE_CMD="docker-compose -f docker-compose.dev.yml"
fi

if ! $COMPOSE_CMD exec -T app php artisan key:generate --show &> /dev/null; then
    print_message "Generando clave de aplicación..."
    $COMPOSE_CMD exec -T app php artisan key:generate
else
    print_message "Clave de aplicación ya existe"
fi

# Ejecutar migraciones
print_message "Ejecutando migraciones de base de datos..."
$COMPOSE_CMD exec -T app php artisan migrate --force

# Crear enlace simbólico para storage
print_message "Creando enlace simbólico para storage..."
$COMPOSE_CMD exec -T app php artisan storage:link || true

# En producción, los assets ya se compilaron durante el build del Dockerfile
# Solo compilar en desarrollo si es necesario
if [ "$ENVIRONMENT" != "production" ]; then
    print_message "Instalando dependencias de Node.js (solo desarrollo)..."
    docker-compose -f docker-compose.dev.yml exec -T app npm install --prefer-offline --no-audit || print_warning "Error al instalar dependencias de Node.js, continuando..."
    print_message "Para compilar assets en desarrollo, ejecuta: docker-compose -f docker-compose.dev.yml exec app npm run dev"
else
    print_message "Assets compilados durante el build del Dockerfile (producción optimizada)"
fi

# Optimizar Laravel para producción
if [ "$ENVIRONMENT" = "production" ]; then
    print_message "Optimizando Laravel para producción..."
    $COMPOSE_CMD exec -T app php artisan config:cache
    $COMPOSE_CMD exec -T app php artisan route:cache
    $COMPOSE_CMD exec -T app php artisan view:cache
    $COMPOSE_CMD exec -T app php artisan event:cache
fi

# Verificar estado de los contenedores
print_message "Verificando estado de los contenedores..."
if [ "$ENVIRONMENT" = "production" ]; then
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml ps
else
    docker-compose -f docker-compose.dev.yml ps
fi

echo ""
echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}  ✓ Despliegue completado exitosamente!${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
echo ""
echo "🌐 Aplicación disponible en: http://localhost:8000"
echo "🗄️  Base de datos disponible en: localhost:3307"
echo ""
echo "Para ver los logs: docker-compose logs -f"
echo "Para detener: docker-compose down"
echo ""
