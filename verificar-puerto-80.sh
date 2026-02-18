#!/bin/bash

# Script para verificar qué está usando el puerto 80

echo "🔍 Verificando qué está usando el puerto 80..."
echo ""

# Método 1: lsof
if command -v lsof &> /dev/null; then
    echo "📋 Usando lsof:"
    sudo lsof -i :80
    echo ""
fi

# Método 2: netstat
if command -v netstat &> /dev/null; then
    echo "📋 Usando netstat:"
    sudo netstat -tulpn | grep :80
    echo ""
fi

# Método 3: ss
if command -v ss &> /dev/null; then
    echo "📋 Usando ss:"
    sudo ss -tulpn | grep :80
    echo ""
fi

# Verificar servicios comunes
echo "🔍 Verificando servicios comunes:"
echo ""

if systemctl is-active --quiet nginx; then
    echo "⚠️  Nginx está corriendo"
    echo "   Detener: sudo systemctl stop nginx"
    echo "   Deshabilitar: sudo systemctl disable nginx"
    echo ""
fi

if systemctl is-active --quiet apache2; then
    echo "⚠️  Apache2 está corriendo"
    echo "   Detener: sudo systemctl stop apache2"
    echo "   Deshabilitar: sudo systemctl disable apache2"
    echo ""
fi

if systemctl is-active --quiet httpd; then
    echo "⚠️  Httpd está corriendo"
    echo "   Detener: sudo systemctl stop httpd"
    echo "   Deshabilitar: sudo systemctl disable httpd"
    echo ""
fi

# Verificar contenedores Docker
echo "🔍 Contenedores Docker usando puerto 80:"
docker ps --format "table {{.Names}}\t{{.Ports}}" | grep :80 || echo "   Ninguno encontrado"
echo ""

echo "💡 Soluciones:"
echo "   1. Detener el servicio que usa el puerto 80"
echo "   2. Usar docker-compose.prod-alt.yml (puerto 8080)"
echo "   3. Configurar nginx del sistema como proxy (ver SOLUCION-PUERTO-80.md)"
