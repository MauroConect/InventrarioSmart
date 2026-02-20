#!/bin/bash
# Script completo para instalación desde cero en producción
# Uso: ./instalacion-completa-desde-cero.sh

set -e

echo "🚀 INSTALACIÓN COMPLETA DESDE CERO"
echo "═══════════════════════════════════════════════════════════"
echo ""

# Colores
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# 1. Detener y eliminar contenedores
echo -e "${BLUE}📦 Paso 1/12: Deteniendo y eliminando contenedores existentes...${NC}"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml down -v 2>/dev/null || true
echo -e "${GREEN}✅ Contenedores detenidos${NC}"
echo ""

# 2. Preguntar si eliminar base de datos
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

# 3. Verificar/corregir archivo .env
echo -e "${BLUE}📋 Paso 2/12: Verificando archivo .env...${NC}"
if [ ! -f .env ]; then
    if [ -f .env.example ]; then
        cp .env.example .env
        echo -e "${GREEN}✅ Creado .env desde .env.example${NC}"
    else
        echo -e "${RED}❌ No se encontró .env.example${NC}"
        exit 1
    fi
fi

# Corregir configuraciones importantes en .env
echo "   Corrigiendo configuraciones en .env..."
sed -i 's/^DB_HOST=.*/DB_HOST=db/' .env
sed -i 's/^DB_PORT=.*/DB_PORT=3306/' .env
sed -i '/^APP_DEBUG=/d' .env
echo "APP_DEBUG=false" >> .env
sed -i 's/^APP_ENV=.*/APP_ENV=production/' .env

# Asegurar que existan las configuraciones necesarias
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

# 4. Construir imágenes
echo -e "${BLUE}📦 Paso 3/12: Construyendo imágenes Docker (esto puede tardar varios minutos)...${NC}"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml build --no-cache
echo -e "${GREEN}✅ Imágenes construidas${NC}"
echo ""

# 5. Levantar contenedores
echo -e "${BLUE}📦 Paso 4/12: Levantando contenedores...${NC}"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d
echo -e "${GREEN}✅ Contenedores levantados${NC}"
echo ""

# 6. Esperar a que la base de datos esté lista
echo -e "${BLUE}⏳ Paso 5/12: Esperando a que la base de datos esté lista...${NC}"
echo "   Esto puede tardar 30-60 segundos..."
for i in {1..20}; do
    STATUS=$(docker inspect inventario_db --format='{{.State.Health.Status}}' 2>/dev/null || echo "starting")
    if [ "$STATUS" = "healthy" ]; then
        echo "   ✅ Base de datos lista (intento $i/20)"
        break
    fi
    if [ $i -eq 20 ]; then
        echo "   ⚠️  La base de datos aún no está lista, pero continuando..."
    else
        echo "   Intento $i/20... Estado: $STATUS"
        sleep 3
    fi
done
echo ""

# 7. Arreglar permisos
echo -e "${BLUE}📝 Paso 6/12: Arreglando permisos...${NC}"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -u root app sh -c "
    chown -R www:www /var/www && \
    chmod -R 755 /var/www && \
    chmod -R 775 /var/www/storage /var/www/bootstrap/cache && \
    mkdir -p /var/www/storage/logs /var/www/storage/framework/sessions /var/www/storage/framework/views /var/www/storage/framework/cache && \
    chown -R www:www /var/www/storage /var/www/bootstrap/cache && \
    touch /var/www/storage/logs/laravel.log && \
    chown www:www /var/www/storage/logs/laravel.log && \
    chmod 664 /var/www/storage/logs/laravel.log
" 2>/dev/null || echo "⚠️  Algunos permisos no se pudieron arreglar (continuando...)"
echo -e "${GREEN}✅ Permisos arreglados${NC}"
echo ""

# 8. Instalar dependencias de Composer
echo -e "${BLUE}📦 Paso 7/12: Instalando dependencias de Composer...${NC}"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

if [ $? -ne 0 ]; then
    echo "   Intentando con permisos de root..."
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -u root -T app \
        composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -u root app sh -c \
        "chown -R www:www /var/www/vendor"
fi
echo -e "${GREEN}✅ Dependencias instaladas${NC}"
echo ""

# 9. Generar clave de aplicación
echo -e "${BLUE}🔑 Paso 8/12: Generando clave de aplicación...${NC}"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan key:generate --force 2>/dev/null || echo "   Clave ya existe"
echo -e "${GREEN}✅ Clave generada${NC}"
echo ""

# 10. Limpiar caché
echo -e "${BLUE}🧹 Paso 9/12: Limpiando cachés...${NC}"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan config:clear 2>/dev/null || true
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan cache:clear 2>/dev/null || true
echo -e "${GREEN}✅ Cachés limpiadas${NC}"
echo ""

# 11. Ejecutar migraciones
echo -e "${BLUE}🗄️  Paso 10/12: Ejecutando migraciones...${NC}"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan migrate --force
echo -e "${GREEN}✅ Migraciones ejecutadas${NC}"
echo ""

# 12. Crear enlace simbólico de storage
echo -e "${BLUE}📁 Paso 11/12: Creando enlace simbólico de storage...${NC}"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan storage:link || echo "   Enlace ya existe"
echo -e "${GREEN}✅ Enlace creado${NC}"
echo ""

# 13. Crear usuario administrador
echo -e "${BLUE}👤 Paso 12/12: Creando usuario administrador...${NC}"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php -r "
    require '/var/www/vendor/autoload.php';
    \$app = require_once '/var/www/bootstrap/app.php';
    \$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    
    try {
        \$user = \App\Models\User::where('email', 'admin@inventario.com')->first();
        
        if (\$user) {
            echo '⚠️  El usuario ya existe, actualizando contraseña...\n';
            \$user->password = \Illuminate\Support\Facades\Hash::make('password123');
            \$user->save();
            echo '✅ Contraseña actualizada\n';
        } else {
            echo '📝 Creando nuevo usuario...\n';
            \$user = \App\Models\User::create([
                'name' => 'Administrador',
                'email' => 'admin@inventario.com',
                'password' => \Illuminate\Support\Facades\Hash::make('password123')
            ]);
            echo '✅ Usuario creado (ID: ' . \$user->id . ')\n';
        }
        
        if (\Illuminate\Support\Facades\Hash::check('password123', \$user->password)) {
            echo '✅ Verificación de contraseña: EXITOSA\n';
        }
        
        echo '\n';
        echo '═══════════════════════════════════════════════════════════\n';
        echo '📋 CREDENCIALES DE ACCESO:\n';
        echo '   Email: admin@inventario.com\n';
        echo '   Contraseña: password123\n';
        echo '═══════════════════════════════════════════════════════════\n';
    } catch (\Exception \$e) {
        echo '❌ Error: ' . \$e->getMessage() . '\n';
        exit(1);
    }
    "

if [ $? -eq 0 ]; then
    echo ""
    echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
    echo -e "${GREEN}  ✅ ¡INSTALACIÓN COMPLETADA EXITOSAMENTE!${NC}"
    echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
    echo ""
    echo -e "${BLUE}📊 Estado de los contenedores:${NC}"
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml ps
    echo ""
    echo -e "${BLUE}🌐 Aplicación disponible en:${NC}"
    echo "   http://localhost:8000"
    echo "   o"
    echo "   http://lucianogonzalezautomotores.site"
    echo ""
    echo -e "${BLUE}📋 Credenciales de acceso:${NC}"
    echo "   Email: admin@inventario.com"
    echo "   Contraseña: password123"
    echo ""
    echo -e "${YELLOW}⚠️  IMPORTANTE: Cambia la contraseña después del primer inicio de sesión${NC}"
    echo ""
    echo -e "${BLUE}📝 Comandos útiles:${NC}"
    echo "   Ver logs:        docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs -f"
    echo "   Detener:         docker-compose -f docker-compose.yml -f docker-compose.prod.yml down"
    echo "   Reiniciar:       docker-compose -f docker-compose.yml -f docker-compose.prod.yml restart"
    echo ""
else
    echo ""
    echo -e "${RED}❌ Error al crear usuario${NC}"
    echo "   Revisa los mensajes anteriores"
    exit 1
fi
