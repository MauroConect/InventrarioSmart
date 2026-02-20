#!/bin/bash
# Script para desplegar el proyecto con Blade (sin React/Vite)
# Uso: ./desplegar-blade.sh

set -e

echo "🚀 DESPLIEGUE CON BLADE"
echo "═══════════════════════════════════════════════════════════"
echo ""

# Colores
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# 1. Detener contenedores existentes
echo -e "${BLUE}📦 Paso 1/10: Deteniendo contenedores existentes...${NC}"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml down 2>/dev/null || true
echo -e "${GREEN}✅ Contenedores detenidos${NC}"
echo ""

# 2. Verificar/corregir archivo .env
echo -e "${BLUE}📋 Paso 2/10: Verificando archivo .env...${NC}"
if [ ! -f .env ]; then
    if [ -f .env.example ]; then
        cp .env.example .env
        echo -e "${GREEN}✅ Creado .env desde .env.example${NC}"
    else
        echo -e "${RED}❌ No se encontró .env.example${NC}"
        exit 1
    fi
fi

# Corregir configuraciones importantes
echo "   Corrigiendo configuraciones en .env..."
sed -i 's/^DB_HOST=.*/DB_HOST=db/' .env
sed -i 's/^DB_PORT=.*/DB_PORT=3306/' .env
sed -i '/^APP_DEBUG=/d' .env
echo "APP_DEBUG=false" >> .env
sed -i 's/^APP_ENV=.*/APP_ENV=production/' .env

# Asegurar configuraciones necesarias
if ! grep -q "^DB_DATABASE=" .env; then
    echo "DB_DATABASE=inventario_db" >> .env
fi
if ! grep -q "^DB_USERNAME=" .env; then
    echo "DB_USERNAME=inventario_user" >> .env
fi
if ! grep -q "^DB_PASSWORD=" .env; then
    echo "DB_PASSWORD=root" >> .env
fi

echo -e "${GREEN}✅ Archivo .env configurado${NC}"
echo ""

# 3. Construir imágenes (sin compilar assets de React)
echo -e "${BLUE}📦 Paso 3/10: Construyendo imágenes Docker...${NC}"
echo -e "${YELLOW}   Nota: No se compilarán assets de React (usando Blade)${NC}"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml build --no-cache
echo -e "${GREEN}✅ Imágenes construidas${NC}"
echo ""

# 4. Levantar contenedores
echo -e "${BLUE}📦 Paso 4/10: Levantando contenedores...${NC}"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d
echo -e "${GREEN}✅ Contenedores levantados${NC}"
echo ""

# 5. Esperar a que la base de datos esté lista
echo -e "${BLUE}⏳ Paso 5/10: Esperando a que la base de datos esté lista...${NC}"
for i in {1..20}; do
    STATUS=$(docker inspect inventario_db --format='{{.State.Health.Status}}' 2>/dev/null || echo "starting")
    if [ "$STATUS" = "healthy" ]; then
        echo "   ✅ Base de datos lista"
        break
    fi
    if [ $i -eq 20 ]; then
        echo "   ⚠️  La base de datos aún no está lista, pero continuando..."
    else
        sleep 3
    fi
done
echo ""

# 6. Arreglar permisos
echo -e "${BLUE}🔧 Paso 6/10: Arreglando permisos...${NC}"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app sh -c "
    chown -R www:www /var/www/storage /var/www/bootstrap/cache && \
    chmod -R 775 /var/www/storage /var/www/bootstrap/cache && \
    touch /var/www/storage/logs/laravel.log && \
    chmod 664 /var/www/storage/logs/laravel.log
" || echo -e "${YELLOW}⚠️  No se pudieron arreglar permisos dentro del contenedor${NC}"

# También en el host si los volúmenes están montados
if [ -d "storage" ]; then
    chown -R $(id -u):$(id -g) storage bootstrap/cache 2>/dev/null || true
    chmod -R 775 storage bootstrap/cache 2>/dev/null || true
    touch storage/logs/laravel.log 2>/dev/null || true
    chmod 664 storage/logs/laravel.log 2>/dev/null || true
fi
echo -e "${GREEN}✅ Permisos configurados${NC}"
echo ""

# 7. Instalar dependencias de Composer
echo -e "${BLUE}📦 Paso 7/10: Instalando dependencias de Composer...${NC}"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
echo -e "${GREEN}✅ Dependencias instaladas${NC}"
echo ""

# 8. Configurar Laravel
echo -e "${BLUE}⚙️  Paso 8/10: Configurando Laravel...${NC}"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan key:generate --force 2>/dev/null || echo "   Clave ya existe"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan config:clear
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan cache:clear
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan view:clear
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan route:clear
echo -e "${GREEN}✅ Laravel configurado${NC}"
echo ""

# 9. Ejecutar migraciones
echo -e "${BLUE}🗄️  Paso 9/10: Ejecutando migraciones...${NC}"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan migrate --force
echo -e "${GREEN}✅ Migraciones ejecutadas${NC}"
echo ""

# 10. Crear enlace simbólico de storage
echo -e "${BLUE}🔗 Paso 10/10: Creando enlace simbólico de storage...${NC}"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan storage:link 2>/dev/null || echo "   Enlace ya existe"
echo -e "${GREEN}✅ Enlace creado${NC}"
echo ""

# Resumen
echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}✅ DESPLIEGUE COMPLETADO${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
echo ""
echo -e "${BLUE}📋 Próximos pasos:${NC}"
echo -e "   1. Verificar que los contenedores estén corriendo:"
echo -e "      ${YELLOW}docker-compose -f docker-compose.yml -f docker-compose.prod.yml ps${NC}"
echo ""
echo -e "   2. Ver logs si hay problemas:"
echo -e "      ${YELLOW}docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs app${NC}"
echo ""
echo -e "   3. Crear usuario administrador:"
echo -e "      ${YELLOW}docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan tinker${NC}"
echo -e "      ${YELLOW}\\App\\Models\\User::create(['name' => 'Admin', 'email' => 'admin@inventario.com', 'password' => bcrypt('password123')]);${NC}"
echo ""
echo -e "   4. Acceder a la aplicación:"
echo -e "      ${YELLOW}http://tu-dominio:8000${NC}"
echo ""
