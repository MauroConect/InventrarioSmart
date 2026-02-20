#!/bin/bash
# Solución para el error "Container is unhealthy"

echo "🔧 Solucionando error de contenedor unhealthy..."
echo ""

# 1. Detener todo
echo "📦 Paso 1/4: Deteniendo contenedores..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml down
echo "✅ Contenedores detenidos"
echo ""

# 2. Levantar solo la base de datos primero
echo "📦 Paso 2/4: Levantando base de datos..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d db
echo "✅ Base de datos iniciada"
echo ""

# 3. Esperar a que la BD esté lista
echo "⏳ Paso 3/4: Esperando a que la base de datos esté lista..."
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

# 4. Levantar app y webserver
echo "📦 Paso 4/4: Levantando aplicación y webserver..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d app webserver
echo "✅ Aplicación y webserver iniciados"
echo ""

# Verificar estado
echo "📊 Estado final de los contenedores:"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml ps
echo ""

echo "✅ Proceso completado!"
echo ""
echo "💡 Si aún hay problemas, revisa los logs con:"
echo "   docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs app"
