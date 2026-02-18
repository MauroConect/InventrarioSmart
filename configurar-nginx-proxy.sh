#!/bin/bash

# Script para configurar Nginx como proxy reverso para Inventario Inteligente

echo "🔍 Verificando archivos existentes en /etc/nginx/sites-available..."
echo ""

# Listar archivos existentes
ls -la /etc/nginx/sites-available/

echo ""
echo "📋 Archivos encontrados:"
echo "   - app.conf"
echo "   - app.confe"
echo "   - default"
echo "   - lucianogonzalez"
echo ""

# Verificar si ya existe uno para inventario
if [ -f /etc/nginx/sites-available/inventario ]; then
    echo "⚠️  Ya existe un archivo 'inventario'"
    read -p "¿Deseas sobrescribirlo? (s/n): " respuesta
    if [ "$respuesta" != "s" ] && [ "$respuesta" != "S" ]; then
        echo "Cancelado."
        exit 1
    fi
fi

# Pedir información
echo "📝 Configuración del proxy:"
read -p "¿Qué dominio o IP usarás? (ej: inventario.tudominio.com o IP del servidor): " DOMAIN
read -p "¿En qué puerto interno está corriendo Docker? (por defecto 8000): " PORT
PORT=${PORT:-8000}

# Crear configuración
cat > /tmp/inventario-nginx.conf << EOF
server {
    listen 80;
    server_name ${DOMAIN};

    # Logs
    access_log /var/log/nginx/inventario-access.log;
    error_log /var/log/nginx/inventario-error.log;

    # Tamaño máximo de upload
    client_max_body_size 20M;

    location / {
        proxy_pass http://localhost:${PORT};
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        
        # Timeouts
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
        
        # Para WebSockets (si los usas en el futuro)
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection "upgrade";
    }

    # Cache de assets estáticos (opcional, mejora rendimiento)
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        proxy_pass http://localhost:${PORT};
        proxy_set_header Host \$host;
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}
EOF

echo ""
echo "✅ Configuración creada en /tmp/inventario-nginx.conf"
echo ""
echo "📋 Vista previa:"
cat /tmp/inventario-nginx.conf
echo ""

read -p "¿Deseas instalar esta configuración? (s/n): " instalar
if [ "$instalar" != "s" ] && [ "$instalar" != "S" ]; then
    echo "Cancelado. La configuración está en /tmp/inventario-nginx.conf"
    exit 0
fi

# Copiar a sites-available
sudo cp /tmp/inventario-nginx.conf /etc/nginx/sites-available/inventario

# Habilitar el sitio
if [ -L /etc/nginx/sites-enabled/inventario ]; then
    echo "⚠️  El enlace simbólico ya existe, eliminándolo..."
    sudo rm /etc/nginx/sites-enabled/inventario
fi

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
echo "✅ Configuración completada!"
echo ""
echo "📝 Próximos pasos:"
echo "   1. Asegúrate de que Docker esté corriendo en el puerto ${PORT}"
echo "   2. Verifica que la aplicación funcione: http://${DOMAIN}"
echo "   3. Para SSL/HTTPS, ejecuta: sudo certbot --nginx -d ${DOMAIN}"
