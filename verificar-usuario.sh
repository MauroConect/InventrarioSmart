#!/bin/bash
# Script para verificar y crear usuario en producción

echo "🔧 Verificando y creando usuario administrador..."
echo ""

# Verificar que el contenedor esté corriendo
if ! docker ps | grep -q inventario_app; then
    echo "❌ El contenedor inventario_app no está corriendo"
    exit 1
fi

# Copiar el script PHP al contenedor
echo "📦 Copiando script al contenedor..."
docker cp create-user.php inventario_app:/var/www/create-user.php

# Ejecutar el script
echo "🚀 Ejecutando script de creación de usuario..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php create-user.php

# Limpiar
echo ""
echo "🧹 Limpiando archivo temporal..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    rm -f /var/www/create-user.php

echo ""
echo "✅ Proceso completado!"
