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
docker-compose down

# Construir y levantar contenedores
print_message "Construyendo y levantando contenedores Docker..."
docker-compose up -d --build

# Esperar a que la base de datos esté lista
print_message "Esperando a que la base de datos esté lista..."
sleep 15

# Instalar dependencias de Composer
print_message "Instalando dependencias de PHP (Composer)..."
docker-compose exec -T app composer install --no-interaction --prefer-dist --optimize-autoloader

# Generar clave de aplicación si no existe
if ! docker-compose exec -T app php artisan key:generate --show &> /dev/null; then
    print_message "Generando clave de aplicación..."
    docker-compose exec -T app php artisan key:generate
else
    print_message "Clave de aplicación ya existe"
fi

# Ejecutar migraciones
print_message "Ejecutando migraciones de base de datos..."
docker-compose exec -T app php artisan migrate --force

# Crear enlace simbólico para storage
print_message "Creando enlace simbólico para storage..."
docker-compose exec -T app php artisan storage:link || true

# Instalar dependencias de Node.js
print_message "Instalando dependencias de Node.js..."
docker-compose exec -T app npm install

# Compilar assets
if [ "$ENVIRONMENT" = "production" ]; then
    print_message "Compilando assets para producción..."
    docker-compose exec -T app npm run build
else
    print_message "Compilando assets para desarrollo..."
    docker-compose exec -T app npm run build
fi

# Optimizar Laravel para producción
if [ "$ENVIRONMENT" = "production" ]; then
    print_message "Optimizando Laravel para producción..."
    docker-compose exec -T app php artisan config:cache
    docker-compose exec -T app php artisan route:cache
    docker-compose exec -T app php artisan view:cache
fi

# Verificar estado de los contenedores
print_message "Verificando estado de los contenedores..."
docker-compose ps

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
