#!/bin/bash
# Script para ver la configuración de Nginx

echo "📋 CONFIGURACIÓN DE NGINX"
echo "═══════════════════════════════════════════════════════════"
echo ""

echo -e "${BLUE}1. Configuración activa en el contenedor:${NC}"
echo "═══════════════════════════════════════════════════════════"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T webserver cat /etc/nginx/conf.d/default.conf
echo ""

echo -e "${BLUE}2. Archivo de configuración local (prod.conf):${NC}"
echo "═══════════════════════════════════════════════════════════"
cat docker/nginx/prod.conf
echo ""

echo -e "${BLUE}3. Verificando sintaxis de Nginx:${NC}"
echo "═══════════════════════════════════════════════════════════"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T webserver nginx -t
echo ""

echo -e "${BLUE}4. Logs de error de Nginx:${NC}"
echo "═══════════════════════════════════════════════════════════"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T webserver tail -20 /var/log/nginx/error.log
echo ""
