#!/bin/bash

# Script para instalar configuración de Nginx para lucianogonzalezautomotores.site

set -e

echo "🚀 Configurando Nginx para lucianogonzalezautomotores.site"
echo ""

# Verificar que el archivo de configuración existe
if [ ! -f nginx-inventario.conf ]; then
    echo "❌ Error: No se encontró nginx-inventario.conf"
    exit 1
fi

# Copiar configuración
echo "📋 Copiando configuración a /etc/nginx/sites-available/inventario..."
sudo cp nginx-inventario.conf /etc/nginx/sites-available/inventario

# Habilitar el sitio
if [ -L /etc/nginx/sites-enabled/inventario ]; then
    echo "⚠️  El enlace simbólico ya existe, eliminándolo..."
    sudo rm /etc/nginx/sites-enabled/inventario
fi

echo "🔗 Creando enlace simbólico..."
sudo ln -s /etc/nginx/sites-available/inventario /etc/nginx/sites-enabled/

# Verificar configuración
echo ""
echo "🔍 Verificando configuración de Nginx..."
if sudo nginx -t; then
    echo ""
    echo "✅ Configuración válida!"
    echo ""
    read -p "¿Deseas recargar Nginx ahora? (s/n): " recargar
    if [ "$recargar" = "s" ] || [ "$recargar" = "S" ]; then
        sudo systemctl reload nginx
        echo "✅ Nginx recargado"
    else
        echo "⚠️  Recuerda ejecutar: sudo systemctl reload nginx"
    fi
else
    echo ""
    echo "❌ Error en la configuración. Revisa los mensajes arriba."
    exit 1
fi

echo ""
echo "✅ Configuración de Nginx completada!"
echo ""
echo "📝 Próximos pasos:"
echo "   1. Asegúrate de que docker-compose.prod.yml use el puerto 8000"
echo "   2. Levanta los contenedores: ./deploy-prod.sh"
echo "   3. Verifica que funcione: http://lucianogonzalezautomotores.site"
echo "   4. Para SSL/HTTPS, ejecuta: sudo certbot --nginx -d lucianogonzalezautomotores.site -d www.lucianogonzalezautomotores.site"
echo ""
