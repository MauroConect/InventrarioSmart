#!/bin/bash
# Script para diagnosticar error 500 en el servidor

echo "🔍 DIAGNÓSTICO DE ERROR 500"
echo "═══════════════════════════════════════════════════════════"
echo ""

# Colores
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}📋 Paso 1: Verificando estado de contenedores...${NC}"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml ps
echo ""

echo -e "${BLUE}📋 Paso 2: Revisando logs de Nginx (últimas 50 líneas)...${NC}"
echo -e "${YELLOW}═══════════════════════════════════════════════════════════${NC}"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs --tail=50 webserver
echo -e "${YELLOW}═══════════════════════════════════════════════════════════${NC}"
echo ""

echo -e "${BLUE}📋 Paso 3: Revisando logs de Laravel (últimas 50 líneas)...${NC}"
echo -e "${YELLOW}═══════════════════════════════════════════════════════════${NC}"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs --tail=50 app
echo -e "${YELLOW}═══════════════════════════════════════════════════════════${NC}"
echo ""

echo -e "${BLUE}📋 Paso 4: Revisando archivo de log de Laravel...${NC}"
echo -e "${YELLOW}═══════════════════════════════════════════════════════════${NC}"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app tail -50 /var/www/storage/logs/laravel.log 2>/dev/null || echo "No se pudo leer el archivo de log"
echo -e "${YELLOW}═══════════════════════════════════════════════════════════${NC}"
echo ""

echo -e "${BLUE}📋 Paso 5: Verificando configuración de Nginx...${NC}"
echo -e "${YELLOW}═══════════════════════════════════════════════════════════${NC}"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T webserver cat /etc/nginx/conf.d/default.conf
echo -e "${YELLOW}═══════════════════════════════════════════════════════════${NC}"
echo ""

echo -e "${BLUE}📋 Paso 6: Verificando permisos de storage...${NC}"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app ls -la /var/www/storage/logs/ 2>/dev/null || echo "No se pudo verificar permisos"
echo ""

echo -e "${BLUE}📋 Paso 7: Verificando conexión a la base de datos...${NC}"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan tinker --execute="try { DB::connection()->getPdo(); echo 'Conexión OK'; } catch (Exception \$e) { echo 'Error: ' . \$e->getMessage(); }" 2>/dev/null || echo "No se pudo verificar conexión"
echo ""

echo -e "${BLUE}📋 Paso 8: Verificando configuración de Laravel...${NC}"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan config:show app.url 2>/dev/null || echo "No se pudo verificar configuración"
echo ""

echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}✅ Diagnóstico completado${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
echo ""
echo -e "${YELLOW}💡 Comandos útiles para más información:${NC}"
echo "   Ver logs en tiempo real: docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs -f"
echo "   Ver solo errores de Nginx: docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs webserver | grep error"
echo "   Ver solo errores de Laravel: docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs app | grep -i error"
echo ""
