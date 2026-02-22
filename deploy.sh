#!/bin/bash

# Script de despliegue para Inventario Inteligente
# Uso: ./deploy.sh [produccion|desarrollo]

set -e  # Salir si hay algún error

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Función para imprimir mensajes
print_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Determinar modo de despliegue
MODE=${1:-produccion}

if [ "$MODE" != "produccion" ] && [ "$MODE" != "desarrollo" ]; then
    print_error "Modo inválido. Usa: produccion o desarrollo"
    exit 1
fi

print_info "Iniciando despliegue en modo: $MODE"

# Verificar que Docker está corriendo
if ! docker info > /dev/null 2>&1; then
    print_error "Docker no está corriendo. Por favor inicia Docker."
    exit 1
fi

# Verificar que docker-compose está instalado
if ! command -v docker-compose &> /dev/null; then
    print_error "docker-compose no está instalado."
    exit 1
fi

# Verificar que existe el archivo .env
if [ ! -f .env ]; then
    print_warning "Archivo .env no encontrado. Creando desde .env.example..."
    if [ -f .env.example ]; then
        cp .env.example .env
        print_warning "Por favor configura el archivo .env antes de continuar."
        exit 1
    else
        print_error "No existe .env ni .env.example"
        exit 1
    fi
fi

# Configurar comandos según el modo
if [ "$MODE" = "produccion" ]; then
    COMPOSE_CMD="docker-compose -f docker-compose.yml -f docker-compose.prod.yml"
    print_info "Usando configuración de producción"
else
    COMPOSE_CMD="docker-compose"
    print_info "Usando configuración de desarrollo"
fi

# Paso 1: Detener contenedores existentes
print_info "Deteniendo contenedores existentes..."
$COMPOSE_CMD down

# Paso 2: Construir imágenes (solo si es necesario)
print_info "Construyendo imágenes Docker..."
$COMPOSE_CMD build --no-cache app

# Paso 3: Iniciar servicios
print_info "Iniciando servicios..."
$COMPOSE_CMD up -d

# Esperar a que los servicios estén listos
print_info "Esperando a que los servicios estén listos..."
sleep 10

# Verificar que el contenedor app está corriendo
if ! $COMPOSE_CMD ps app | grep -q "Up"; then
    print_error "El contenedor app no está corriendo. Revisa los logs:"
    $COMPOSE_CMD logs app --tail=50
    exit 1
fi

# Paso 4: Instalar dependencias de Composer
print_info "Instalando dependencias de Composer..."
$COMPOSE_CMD exec -T app composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Paso 5: Verificar que vendor/autoload.php existe
if ! $COMPOSE_CMD exec -T app test -f /var/www/vendor/autoload.php; then
    print_error "vendor/autoload.php no existe después de composer install"
    exit 1
fi
print_info "✓ Dependencias instaladas correctamente"

# Paso 6: Configurar permisos
print_info "Configurando permisos..."
# Para volúmenes montados, ejecutar como root usando docker exec directamente
CONTAINER_NAME=$($COMPOSE_CMD ps -q app)
if [ ! -z "$CONTAINER_NAME" ]; then
    docker exec -u root $CONTAINER_NAME sh -c "chmod -R 775 /var/www/storage /var/www/bootstrap/cache || true"
    docker exec -u root $CONTAINER_NAME sh -c "chown -R www:www /var/www/storage /var/www/bootstrap/cache || true"
else
    print_warning "No se pudo obtener el nombre del contenedor, saltando configuración de permisos"
fi

# Paso 7: Ejecutar migraciones
print_info "Ejecutando migraciones..."
$COMPOSE_CMD exec -T app php artisan migrate --force

# Paso 8: Limpiar cachés
print_info "Limpiando cachés..."
$COMPOSE_CMD exec -T app php artisan config:clear
$COMPOSE_CMD exec -T app php artisan cache:clear
$COMPOSE_CMD exec -T app php artisan route:clear
$COMPOSE_CMD exec -T app php artisan view:clear

# Paso 9: Optimizar para producción (solo en modo producción)
if [ "$MODE" = "produccion" ]; then
    print_info "Optimizando para producción..."
    $COMPOSE_CMD exec -T app php artisan config:cache
    $COMPOSE_CMD exec -T app php artisan route:cache
    $COMPOSE_CMD exec -T app php artisan view:cache
fi

# Paso 10: Verificar estado de los servicios
print_info "Verificando estado de los servicios..."
$COMPOSE_CMD ps

# Paso 11: Mostrar logs recientes
print_info "Últimos logs del contenedor app:"
$COMPOSE_CMD logs app --tail=20

# Paso 12: Verificar que la aplicación responde
print_info "Verificando que la aplicación responde..."
sleep 3
if curl -f http://localhost:8000 > /dev/null 2>&1; then
    print_info "✓ La aplicación está respondiendo en http://localhost:8000"
else
    print_warning "La aplicación no responde en http://localhost:8000. Revisa los logs."
fi

print_info ""
print_info "=========================================="
print_info "Despliegue completado!"
print_info "=========================================="
print_info ""
print_info "Accede a la aplicación en: http://localhost:8000"
print_info ""
print_info "Comandos útiles:"
print_info "  Ver logs: $COMPOSE_CMD logs -f"
print_info "  Detener: $COMPOSE_CMD down"
print_info "  Reiniciar: $COMPOSE_CMD restart"
print_info "  Acceder al contenedor: $COMPOSE_CMD exec app sh"
print_info ""
