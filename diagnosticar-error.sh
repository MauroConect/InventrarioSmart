#!/bin/bash
# Script para diagnosticar el error del contenedor unhealthy

echo "🔍 Diagnosticando el problema..."
echo ""

# Ver estado de los contenedores
echo "📊 Estado de los contenedores:"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml ps
echo ""

# Ver logs del contenedor de la app
echo "📋 Logs del contenedor app (últimas 50 líneas):"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs --tail=50 app
echo ""

# Ver logs de la base de datos
echo "📋 Logs del contenedor db (últimas 30 líneas):"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs --tail=30 db
echo ""

# Verificar si el contenedor está corriendo
echo "🔍 Verificando contenedores:"
docker ps -a | grep inventario
echo ""

# Verificar salud del contenedor de la base de datos
echo "🏥 Estado de salud de la base de datos:"
docker inspect inventario_db --format='{{json .State.Health}}' 2>/dev/null | python3 -m json.tool 2>/dev/null || docker inspect inventario_db --format='{{.State.Health.Status}}' 2>/dev/null || echo "No se pudo obtener información de salud"
echo ""

echo "✅ Diagnóstico completado"
echo ""
echo "💡 Posibles soluciones:"
echo "   1. Si la BD no está healthy, espera más tiempo (puede tardar 30-60 segundos)"
echo "   2. Si hay errores en los logs, revisa la configuración"
echo "   3. Intenta levantar sin el healthcheck: docker-compose up -d --no-deps app"
