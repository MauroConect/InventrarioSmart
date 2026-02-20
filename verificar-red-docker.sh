#!/bin/bash
# Script para verificar y corregir la red de Docker

echo "🔍 Verificando red de Docker..."
echo ""

# 1. Verificar que los contenedores estén corriendo
echo "📊 Paso 1/5: Estado de los contenedores..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml ps
echo ""

# 2. Verificar redes
echo "🌐 Paso 2/5: Verificando redes de Docker..."
docker network ls | grep inventario
echo ""

# 3. Verificar que los contenedores estén en la misma red
echo "🔗 Paso 3/5: Verificando conexión de contenedores a la red..."
echo "Contenedor app:"
docker inspect inventario_app --format='{{range .NetworkSettings.Networks}}{{.NetworkID}} {{end}}' 2>/dev/null || echo "Contenedor no encontrado"
echo ""

echo "Contenedor db:"
docker inspect inventario_db --format='{{range .NetworkSettings.Networks}}{{.NetworkID}} {{end}}' 2>/dev/null || echo "Contenedor no encontrado"
echo ""

# 4. Probar resolución DNS desde el contenedor app
echo "🔍 Paso 4/5: Probando resolución DNS desde el contenedor app..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    ping -c 2 db 2>/dev/null || \
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    nslookup db 2>/dev/null || \
    echo "⚠️  No se pudo resolver 'db'"
echo ""

# 5. Solución: Reiniciar contenedores
echo "🔧 Paso 5/5: Solución recomendada..."
echo ""
echo "Si los contenedores no están en la misma red, ejecuta:"
echo ""
echo "  # Detener todo"
echo "  docker-compose -f docker-compose.yml -f docker-compose.prod.yml down"
echo ""
echo "  # Levantar nuevamente"
echo "  docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d"
echo ""
echo "  # Esperar a que la BD esté lista"
echo "  sleep 30"
echo ""
echo "  # Verificar conexión"
echo "  docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app ping -c 2 db"
echo ""
