#!/bin/bash
# Script para verificar y corregir el archivo .env

echo "🔍 Verificando configuración del .env..."
echo ""

# Colores
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Verificar que existe .env
if [ ! -f .env ]; then
    echo -e "${RED}❌ El archivo .env no existe${NC}"
    exit 1
fi

echo -e "${BLUE}📋 Verificando configuraciones críticas...${NC}"
echo ""

# 1. Verificar APP_DEBUG
if grep -q "^APP_DEBUG=true" .env; then
    echo -e "${RED}❌ APP_DEBUG está en 'true' - Debe ser 'false' en producción${NC}"
    echo -e "${YELLOW}   Corrigiendo...${NC}"
    sed -i 's/^APP_DEBUG=true/APP_DEBUG=false/' .env
    echo -e "${GREEN}   ✅ Corregido${NC}"
elif grep -q "^APP_DEBUG=false" .env; then
    echo -e "${GREEN}✅ APP_DEBUG=false (correcto)${NC}"
else
    echo -e "${YELLOW}⚠️  APP_DEBUG no encontrado, agregando...${NC}"
    echo "APP_DEBUG=false" >> .env
    echo -e "${GREEN}   ✅ Agregado${NC}"
fi

# 2. Verificar DB_HOST
if grep -q "^DB_HOST=db" .env; then
    echo -e "${GREEN}✅ DB_HOST=db (correcto)${NC}"
elif grep -q "^DB_HOST=localhost" .env || grep -q "^DB_HOST=127.0.0.1" .env; then
    echo -e "${RED}❌ DB_HOST está mal configurado (debe ser 'db')${NC}"
    echo -e "${YELLOW}   Corrigiendo...${NC}"
    sed -i 's/^DB_HOST=.*/DB_HOST=db/' .env
    echo -e "${GREEN}   ✅ Corregido${NC}"
else
    echo -e "${YELLOW}⚠️  DB_HOST no encontrado o incorrecto${NC}"
fi

# 3. Verificar DB_PORT
if grep -q "^DB_PORT=3306" .env; then
    echo -e "${GREEN}✅ DB_PORT=3306 (correcto)${NC}"
elif grep -q "^DB_PORT=3307" .env; then
    echo -e "${RED}❌ DB_PORT está en 3307 (debe ser 3306)${NC}"
    echo -e "${YELLOW}   Corrigiendo...${NC}"
    sed -i 's/^DB_PORT=3307/DB_PORT=3306/' .env
    echo -e "${GREEN}   ✅ Corregido${NC}"
else
    echo -e "${YELLOW}⚠️  DB_PORT no encontrado${NC}"
fi

# 4. Verificar APP_ENV
if grep -q "^APP_ENV=production" .env; then
    echo -e "${GREEN}✅ APP_ENV=production (correcto)${NC}"
else
    echo -e "${YELLOW}⚠️  APP_ENV no está en 'production'${NC}"
fi

# 5. Verificar LOG_LEVEL
if grep -q "^LOG_LEVEL=debug" .env; then
    echo -e "${YELLOW}⚠️  LOG_LEVEL=debug (en producción debería ser 'error')${NC}"
    echo -e "${YELLOW}   ¿Deseas cambiarlo a 'error'? (s/n)${NC}"
    read -r respuesta
    if [ "$respuesta" = "s" ] || [ "$respuesta" = "S" ]; then
        sed -i 's/^LOG_LEVEL=debug/LOG_LEVEL=error/' .env
        echo -e "${GREEN}   ✅ Cambiado a 'error'${NC}"
    fi
elif grep -q "^LOG_LEVEL=error" .env; then
    echo -e "${GREEN}✅ LOG_LEVEL=error (correcto para producción)${NC}"
fi

# 6. Verificar duplicados
echo ""
echo -e "${BLUE}🔍 Verificando duplicados...${NC}"
DUPLICADOS=$(grep -E "^(LOG_CHANNEL|LOG_LEVEL)=" .env | sort | uniq -d)
if [ -n "$DUPLICADOS" ]; then
    echo -e "${YELLOW}⚠️  Se encontraron entradas duplicadas:${NC}"
    echo "$DUPLICADOS"
    echo -e "${YELLOW}   Eliminando duplicados...${NC}"
    # Eliminar duplicados, mantener solo la primera ocurrencia
    awk '!seen[$0]++' .env > .env.tmp && mv .env.tmp .env
    echo -e "${GREEN}   ✅ Duplicados eliminados${NC}"
else
    echo -e "${GREEN}✅ No hay duplicados${NC}"
fi

# 7. Verificar APP_KEY
if grep -q "^APP_KEY=$" .env || ! grep -q "^APP_KEY=" .env; then
    echo -e "${RED}❌ APP_KEY no está configurado${NC}"
    echo -e "${YELLOW}   Ejecuta: php artisan key:generate${NC}"
elif grep -q "^APP_KEY=base64:" .env; then
    echo -e "${GREEN}✅ APP_KEY está configurado${NC}"
fi

echo ""
echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}✅ Verificación completada${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
echo ""
echo -e "${BLUE}📋 Resumen de configuración:${NC}"
grep -E "^(APP_ENV|APP_DEBUG|DB_HOST|DB_PORT|LOG_LEVEL)=" .env | sort -u
echo ""
