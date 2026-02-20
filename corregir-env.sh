#!/bin/bash
# Script para corregir el archivo .env

echo "🔧 Corrigiendo archivo .env..."
echo ""

# 1. Corregir DB_PORT (debe ser 3306, no 3307)
echo "📝 Corrigiendo DB_PORT..."
if grep -q "^DB_PORT=3307" .env; then
    sed -i 's/^DB_PORT=3307/DB_PORT=3306/' .env
    echo "✅ DB_PORT corregido de 3307 a 3306"
else
    if ! grep -q "^DB_PORT=" .env; then
        echo "DB_PORT=3306" >> .env
        echo "✅ DB_PORT agregado"
    else
        echo "✅ DB_PORT ya está configurado correctamente"
    fi
fi
echo ""

# 2. Corregir APP_DEBUG (debe ser false en producción)
echo "📝 Corregiendo APP_DEBUG..."
# Eliminar duplicados y dejar solo APP_DEBUG=false
sed -i '/^APP_DEBUG=/d' .env
echo "APP_DEBUG=false" >> .env
echo "✅ APP_DEBUG configurado como false (producción)"
echo ""

# 3. Verificar otras configuraciones importantes
echo "📋 Verificando otras configuraciones..."
echo ""

# Asegurar que DB_HOST sea 'db'
if ! grep -q "^DB_HOST=db" .env; then
    sed -i 's/^DB_HOST=.*/DB_HOST=db/' .env
    echo "✅ DB_HOST corregido a 'db'"
fi

# Asegurar que APP_ENV sea production
if ! grep -q "^APP_ENV=production" .env; then
    sed -i 's/^APP_ENV=.*/APP_ENV=production/' .env
    echo "✅ APP_ENV configurado como production"
fi

echo ""
echo "📋 Configuración final de .env (importante):"
echo "═══════════════════════════════════════════════════════════"
grep "^APP_ENV=" .env
grep "^APP_DEBUG=" .env
grep "^DB_HOST=" .env
grep "^DB_PORT=" .env
grep "^DB_DATABASE=" .env
grep "^DB_USERNAME=" .env
echo "═══════════════════════════════════════════════════════════"
echo ""

# 4. Limpiar caché de configuración
echo "🧹 Limpiando caché de configuración..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan config:clear 2>/dev/null || echo "⚠️  No se pudo limpiar caché (puede ser normal si las dependencias no están instaladas)"
echo ""

echo "✅ Archivo .env corregido"
echo ""
echo "💡 IMPORTANTE:"
echo "   - DB_PORT debe ser 3306 (dentro de Docker)"
echo "   - El puerto 3307 es solo para acceso externo desde el host"
echo "   - APP_DEBUG debe ser false en producción"
echo ""
