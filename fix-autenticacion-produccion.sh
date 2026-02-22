#!/bin/bash

# Script para diagnosticar y corregir problemas de autenticación en producción

set -e

echo "🔍 Diagnosticando problemas de autenticación en producción..."
echo ""

# Verificar que el contenedor está corriendo
if ! docker-compose -f docker-compose.yml -f docker-compose.prod.yml ps app | grep -q "Up"; then
    echo "❌ El contenedor app no está corriendo. Ejecuta primero: ./deploy.sh"
    exit 1
fi

# Obtener el dominio desde APP_URL
APP_URL=$(docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app grep "^APP_URL=" /var/www/.env | cut -d '=' -f2 | tr -d '"' | tr -d "'")
DOMAIN=$(echo $APP_URL | sed -e 's|^[^/]*//||' -e 's|/.*$||' -e 's|:.*$||')

echo "📋 Información detectada:"
echo "   APP_URL: $APP_URL"
echo "   Dominio: $DOMAIN"
echo ""

if [ -z "$DOMAIN" ] || [ "$DOMAIN" = "localhost" ] || [ "$DOMAIN" = "127.0.0.1" ]; then
    echo "⚠️  ADVERTENCIA: El dominio parece ser localhost. Si estás en producción, asegúrate de configurar APP_URL con tu dominio real."
    echo ""
fi

# Verificar configuración actual
echo "🔍 Verificando configuración actual..."
echo ""

# Verificar SANCTUM_STATEFUL_DOMAINS
SANCTUM_DOMAINS=$(docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app grep "^SANCTUM_STATEFUL_DOMAINS=" /var/www/.env | cut -d '=' -f2 || echo "")
if [ -z "$SANCTUM_DOMAINS" ]; then
    echo "⚠️  SANCTUM_STATEFUL_DOMAINS no está configurado en .env"
    echo "   Se usará el valor por defecto de config/sanctum.php"
else
    echo "✓ SANCTUM_STATEFUL_DOMAINS: $SANCTUM_DOMAINS"
fi

# Verificar SESSION_DOMAIN
SESSION_DOMAIN=$(docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app grep "^SESSION_DOMAIN=" /var/www/.env | cut -d '=' -f2 || echo "")
if [ -z "$SESSION_DOMAIN" ]; then
    echo "⚠️  SESSION_DOMAIN no está configurado (se usará null)"
else
    echo "✓ SESSION_DOMAIN: $SESSION_DOMAIN"
fi

# Verificar SESSION_SAME_SITE
SESSION_SAME_SITE=$(docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app grep "^SESSION_SAME_SITE=" /var/www/.env | cut -d '=' -f2 || echo "lax")
echo "✓ SESSION_SAME_SITE: ${SESSION_SAME_SITE:-lax}"

# Verificar SESSION_SECURE_COOKIE
SESSION_SECURE=$(docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app grep "^SESSION_SECURE_COOKIE=" /var/www/.env | cut -d '=' -f2 || echo "false")
echo "✓ SESSION_SECURE_COOKIE: ${SESSION_SECURE:-false}"

echo ""
echo "🔧 Recomendaciones:"
echo ""

# Si el dominio no es localhost, agregar a SANCTUM_STATEFUL_DOMAINS
if [ "$DOMAIN" != "localhost" ] && [ "$DOMAIN" != "127.0.0.1" ]; then
    echo "1. Agregar tu dominio a SANCTUM_STATEFUL_DOMAINS en .env:"
    echo "   SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000,localhost:8000,127.0.0.1,127.0.0.1:8000,::1,$DOMAIN,www.$DOMAIN"
    echo ""
    
    # Preguntar si quiere agregarlo automáticamente
    read -p "¿Quieres agregar automáticamente tu dominio a SANCTUM_STATEFUL_DOMAINS? (s/n): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[SsYy]$ ]]; then
        # Leer el .env actual
        ENV_FILE=".env"
        if [ -f "$ENV_FILE" ]; then
            # Si ya existe SANCTUM_STATEFUL_DOMAINS, actualizarlo
            if grep -q "^SANCTUM_STATEFUL_DOMAINS=" "$ENV_FILE"; then
                # Agregar el dominio si no está ya presente
                if ! grep -q "^SANCTUM_STATEFUL_DOMAINS=.*$DOMAIN" "$ENV_FILE"; then
                    sed -i "s|^SANCTUM_STATEFUL_DOMAINS=\(.*\)|SANCTUM_STATEFUL_DOMAINS=\1,$DOMAIN,www.$DOMAIN|" "$ENV_FILE"
                    echo "✓ Dominio agregado a SANCTUM_STATEFUL_DOMAINS"
                else
                    echo "✓ El dominio ya está en SANCTUM_STATEFUL_DOMAINS"
                fi
            else
                # Agregar nueva línea
                echo "SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000,localhost:8000,127.0.0.1,127.0.0.1:8000,::1,$DOMAIN,www.$DOMAIN" >> "$ENV_FILE"
                echo "✓ SANCTUM_STATEFUL_DOMAINS agregado al .env"
            fi
        fi
    fi
fi

echo ""
echo "2. Si usas HTTPS, asegúrate de tener:"
echo "   SESSION_SECURE_COOKIE=true"
echo "   SESSION_SAME_SITE=none"
echo ""

echo "3. Limpiar cachés y reiniciar:"
echo "   docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan config:clear"
echo "   docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan cache:clear"
echo "   docker-compose -f docker-compose.yml -f docker-compose.prod.yml restart app"
echo ""

# Preguntar si quiere ejecutar los comandos de limpieza
read -p "¿Quieres ejecutar los comandos de limpieza ahora? (s/n): " -n 1 -r
echo
if [[ $REPLY =~ ^[SsYy]$ ]]; then
    echo ""
    echo "🧹 Limpiando cachés..."
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan config:clear
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan cache:clear
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan route:clear
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan view:clear
    
    echo ""
    echo "🔄 Reiniciando contenedor app..."
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml restart app
    
    echo ""
    echo "✅ Limpieza completada. Prueba acceder a la aplicación nuevamente."
fi

echo ""
echo "📝 Si el problema persiste, verifica:"
echo "   1. Que las cookies se estén enviando en las peticiones (F12 > Network > Headers)"
echo "   2. Que el dominio en el navegador coincida con APP_URL"
echo "   3. Que no haya problemas de CORS"
echo ""
