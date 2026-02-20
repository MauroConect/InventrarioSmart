#!/bin/bash
# Script para desplegar el proyecto desde cero en producción
# Uso: ./desplegar-desde-cero.sh

set -e

echo "🚀 DESPLIEGUE COMPLETO DESDE CERO"
echo "═══════════════════════════════════════════════════════════"
echo ""

# Colores
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Verificar Docker
if ! command -v docker &> /dev/null; then
    echo -e "${RED}✗ Docker no está instalado${NC}"
    exit 1
fi

echo -e "${BLUE}📋 Paso 1/10: Deteniendo y eliminando contenedores existentes...${NC}"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml down -v 2>/dev/null || true
echo -e "${GREEN}✅ Contenedores detenidos${NC}"
echo ""

# Preguntar si eliminar volúmenes (base de datos)
echo -e "${YELLOW}⚠ ¿Deseas ELIMINAR la base de datos existente? (s/n)${NC}"
echo -e "${YELLOW}   Esto borrará todos los datos. Presiona 'n' si quieres conservar los datos.${NC}"
read -r respuesta
if [ "$respuesta" = "s" ] || [ "$respuesta" = "S" ]; then
    echo -e "${BLUE}🗑️  Eliminando volúmenes de base de datos...${NC}"
    docker volume rm inventariointeligente_dbdata 2>/dev/null || true
    echo -e "${GREEN}✅ Volúmenes eliminados${NC}"
else
    echo -e "${GREEN}✅ Conservando base de datos existente${NC}"
fi
echo ""

# Verificar .env
echo -e "${BLUE}📋 Paso 2/10: Verificando archivo .env...${NC}"
if [ ! -f .env ]; then
    echo -e "${YELLOW}⚠ Archivo .env no encontrado${NC}"
    if [ -f .env.example ]; then
        cp .env.example .env
        echo -e "${GREEN}✅ Creado .env desde .env.example${NC}"
        echo -e "${YELLOW}⚠ IMPORTANTE: Edita .env con tus configuraciones de producción${NC}"
        echo "Presiona Enter para continuar o Ctrl+C para cancelar..."
        read
    else
        echo -e "${RED}✗ No se encontró .env.example${NC}"
        exit 1
    fi
else
    echo -e "${GREEN}✅ Archivo .env encontrado${NC}"
fi
echo ""

# Construir imágenes
echo -e "${BLUE}📋 Paso 3/10: Construyendo imágenes Docker (esto puede tardar varios minutos)...${NC}"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml build --no-cache
echo -e "${GREEN}✅ Imágenes construidas${NC}"
echo ""

# Levantar contenedores
echo -e "${BLUE}📋 Paso 4/10: Levantando contenedores...${NC}"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d
echo -e "${GREEN}✅ Contenedores levantados${NC}"
echo ""

# Esperar a que la base de datos esté lista
echo -e "${BLUE}📋 Paso 5/10: Esperando a que la base de datos esté lista...${NC}"
echo "   Esto puede tardar 20-30 segundos..."
sleep 25

# Verificar que la base de datos esté lista
echo "   Verificando conexión a la base de datos..."
for i in {1..10}; do
    if docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T db mysqladmin ping -h localhost -u root -proot --silent 2>/dev/null; then
        echo -e "${GREEN}✅ Base de datos lista${NC}"
        break
    fi
    if [ $i -eq 10 ]; then
        echo -e "${RED}✗ La base de datos no está respondiendo después de varios intentos${NC}"
        echo "   Revisa los logs: docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs db"
        exit 1
    fi
    echo "   Intento $i/10..."
    sleep 3
done
echo ""

# Arreglar permisos
echo -e "${BLUE}📋 Paso 6/10: Arreglando permisos...${NC}"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -u root app sh -c "
    chown -R www:www /var/www && \
    chmod -R 755 /var/www && \
    chmod -R 775 /var/www/storage /var/www/bootstrap/cache && \
    mkdir -p /var/www/storage/logs /var/www/storage/framework/sessions /var/www/storage/framework/views /var/www/storage/framework/cache && \
    chown -R www:www /var/www/storage /var/www/bootstrap/cache && \
    touch /var/www/storage/logs/laravel.log && \
    chown www:www /var/www/storage/logs/laravel.log && \
    chmod 664 /var/www/storage/logs/laravel.log
"
echo -e "${GREEN}✅ Permisos arreglados${NC}"
echo ""

# Instalar dependencias de Composer
echo -e "${BLUE}📋 Paso 7/10: Instalando dependencias de PHP (Composer)...${NC}"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
echo -e "${GREEN}✅ Dependencias instaladas${NC}"
echo ""

# Configurar Laravel
echo -e "${BLUE}📋 Paso 8/10: Configurando Laravel...${NC}"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan key:generate --force 2>/dev/null || true

docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan migrate --force

docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan storage:link || true

echo -e "${GREEN}✅ Laravel configurado${NC}"
echo ""

# Optimizar para producción
echo -e "${BLUE}📋 Paso 9/10: Optimizando para producción...${NC}"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan config:cache

docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan route:cache

docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan view:cache

docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan event:cache 2>/dev/null || true

echo -e "${GREEN}✅ Optimización completada${NC}"
echo ""

# Crear usuario administrador
echo -e "${BLUE}📋 Paso 10/10: Creando usuario administrador...${NC}"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php -r "
    require '/var/www/vendor/autoload.php';
    \$app = require_once '/var/www/bootstrap/app.php';
    \$kernel = \$app->make(\Illuminate\Contracts\Console\Kernel::class);
    \$kernel->bootstrap();
    
    try {
        \$user = \App\Models\User::where('email', 'admin@inventario.com')->first();
        
        if (\$user) {
            echo '   Usuario encontrado, actualizando contraseña...\n';
            \$user->password = \Illuminate\Support\Facades\Hash::make('password123');
            \$user->save();
            echo '   ✅ Contraseña actualizada\n';
        } else {
            echo '   Creando nuevo usuario...\n';
            \$user = \App\Models\User::create([
                'name' => 'Administrador',
                'email' => 'admin@inventario.com',
                'password' => \Illuminate\Support\Facades\Hash::make('password123')
            ]);
            echo '   ✅ Usuario creado (ID: ' . \$user->id . ')\n';
        }
        
        if (\Illuminate\Support\Facades\Hash::check('password123', \$user->password)) {
            echo '   ✅ Verificación de contraseña exitosa\n';
        }
    } catch (\Exception \$e) {
        echo '   ❌ Error: ' . \$e->getMessage() . '\n';
        exit(1);
    }
    "

echo ""
echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}  ✓ DESPLIEGUE COMPLETADO EXITOSAMENTE${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
echo ""
echo -e "${BLUE}📊 Estado de los contenedores:${NC}"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml ps
echo ""
echo -e "${BLUE}📋 Credenciales de acceso:${NC}"
echo "   Email: admin@inventario.com"
echo "   Contraseña: password123"
echo ""
echo -e "${YELLOW}⚠️  IMPORTANTE: Cambia la contraseña después del primer inicio de sesión${NC}"
echo ""
echo -e "${BLUE}📝 Comandos útiles:${NC}"
echo "   Ver logs:        docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs -f"
echo "   Ver recursos:    docker stats"
echo "   Detener:         docker-compose -f docker-compose.yml -f docker-compose.prod.yml down"
echo "   Reiniciar:      docker-compose -f docker-compose.yml -f docker-compose.prod.yml restart"
echo ""
echo -e "${GREEN}🌐 Aplicación disponible en: http://localhost:8000${NC}"
echo ""
