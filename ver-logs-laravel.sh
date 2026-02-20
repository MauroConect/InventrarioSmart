#!/bin/bash
# Script para ver los logs de Laravel

echo "📋 LOGS DE LARAVEL"
echo "═══════════════════════════════════════════════════════════"
echo ""

echo -e "${BLUE}Últimas 100 líneas del log:${NC}"
echo "═══════════════════════════════════════════════════════════"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app tail -100 /var/www/storage/logs/laravel.log 2>/dev/null || echo "No se pudo leer el archivo de log"
echo ""

echo -e "${BLUE}Solo errores (últimos 50):${NC}"
echo "═══════════════════════════════════════════════════════════"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app grep -i "error\|exception\|fatal" /var/www/storage/logs/laravel.log | tail -50 2>/dev/null || echo "No se encontraron errores recientes"
echo ""
