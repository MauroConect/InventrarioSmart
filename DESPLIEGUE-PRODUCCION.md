# 🚀 Guía de Despliegue en Producción - Linux

Esta guía te ayudará a desplegar tu aplicación en un servidor Linux de producción.

## 📋 Requisitos Previos

1. **Docker y Docker Compose instalados**
   ```bash
   # Verificar instalación
   docker --version
   docker-compose --version
   ```

2. **Acceso SSH al servidor**
3. **Puertos disponibles**: 80, 443 (y opcionalmente 3306 para MySQL)

## 🔧 Paso 1: Preparar el Servidor

### Instalar Docker (si no está instalado)

```bash
# Actualizar sistema
sudo apt-get update

# Instalar dependencias
sudo apt-get install -y \
    apt-transport-https \
    ca-certificates \
    curl \
    gnupg \
    lsb-release

# Agregar clave GPG de Docker
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg

# Agregar repositorio de Docker
echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu \
  $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

# Instalar Docker
sudo apt-get update
sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin

# Agregar usuario al grupo docker (para no usar sudo)
sudo usermod -aG docker $USER

# Reiniciar sesión o ejecutar:
newgrp docker
```

## 📦 Paso 2: Subir el Código al Servidor

### Opción A: Usando Git (Recomendado)

```bash
# En el servidor
cd /var/www  # o donde quieras desplegar
git clone <tu-repositorio> inventario-inteligente
cd inventario-inteligente
```

### Opción B: Usando SCP/SFTP

```bash
# Desde tu máquina local
scp -r /ruta/local/proyecto usuario@servidor:/var/www/inventario-inteligente
```

## ⚙️ Paso 3: Configurar Variables de Entorno

```bash
# Copiar archivo de ejemplo
cp .env.example .env

# Editar con tus configuraciones de producción
nano .env
```

**Configuraciones importantes en `.env`:**

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tudominio.com

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=inventario_db
DB_USERNAME=inventario_user
DB_PASSWORD=contraseña_segura_aqui

# Para producción, usa contraseñas seguras
MYSQL_ROOT_PASSWORD=contraseña_root_muy_segura
```

## 🚀 Paso 4: Desplegar la Aplicación

### Método 1: Usando el Script Automatizado (Recomendado)

```bash
# Dar permisos de ejecución
chmod +x deploy.sh

# Desplegar en producción
./deploy.sh production
```

### Método 2: Manual (Paso a Paso)

```bash
# 1. Detener contenedores existentes
docker-compose -f docker-compose.yml -f docker-compose.prod.yml down

# 2. Construir y levantar contenedores
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build

# 3. Esperar a que la base de datos esté lista
sleep 20

# 4. Instalar dependencias de PHP (sin dev)
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# 5. Generar clave de aplicación
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan key:generate

# 6. Ejecutar migraciones
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan migrate --force

# 7. Crear enlace simbólico de storage
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan storage:link

# 8. Optimizar Laravel para producción
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan config:cache

docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan route:cache

docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan view:cache

docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan event:cache

# 9. Verificar estado
docker-compose -f docker-compose.yml -f docker-compose.prod.yml ps
```

## ✅ Paso 5: Verificar el Despliegue

```bash
# Ver logs de los contenedores
docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs -f

# Ver consumo de recursos
docker stats

# Verificar que la aplicación responde
curl http://localhost

# Verificar estado de contenedores
docker-compose -f docker-compose.yml -f docker-compose.prod.yml ps
```

## 🔒 Paso 6: Configurar SSL/HTTPS (Opcional pero Recomendado)

### Usando Nginx como Proxy Reverso

Si ya tienes Nginx instalado en el servidor:

```nginx
# /etc/nginx/sites-available/inventario
server {
    listen 80;
    server_name tudominio.com;

    location / {
        proxy_pass http://localhost:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

### Usando Certbot para SSL

```bash
# Instalar Certbot
sudo apt-get install certbot python3-certbot-nginx

# Obtener certificado SSL
sudo certbot --nginx -d tudominio.com
```

## 🔄 Paso 7: Actualizar la Aplicación

Cuando necesites actualizar:

```bash
# 1. Detener contenedores
docker-compose -f docker-compose.yml -f docker-compose.prod.yml down

# 2. Actualizar código (si usas Git)
git pull origin main

# 3. Reconstruir y levantar
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build

# 4. Ejecutar migraciones nuevas (si hay)
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan migrate --force

# 5. Limpiar cache
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan config:clear && \
    php artisan cache:clear && \
    php artisan view:clear

# 6. Reoptimizar
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache
```

## 🛠️ Comandos Útiles

### Ver logs
```bash
# Todos los servicios
docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs -f

# Solo la app
docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs -f app

# Solo los últimos 100 líneas
docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs --tail=100 app
```

### Reiniciar servicios
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml restart
```

### Acceder al contenedor
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app bash
```

### Ejecutar comandos Artisan
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app \
    php artisan [comando]
```

### Backup de base de datos
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec db \
    mysqldump -u root -p${MYSQL_ROOT_PASSWORD} inventario_db > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Restaurar base de datos
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T db \
    mysql -u root -p${MYSQL_ROOT_PASSWORD} inventario_db < backup.sql
```

## 📊 Monitoreo de Recursos

```bash
# Ver consumo en tiempo real
docker stats

# Ver uso de disco
docker system df

# Limpiar recursos no usados (cuidado)
docker system prune -a
```

## 🐛 Solución de Problemas

### El contenedor no inicia
```bash
# Ver logs detallados
docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs app

# Verificar configuración
docker-compose -f docker-compose.yml -f docker-compose.prod.yml config
```

### Error de permisos
```bash
# Ajustar permisos de storage
sudo chown -R $USER:$USER storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

### Puerto ya en uso
```bash
# Ver qué está usando el puerto
sudo netstat -tulpn | grep :80

# Cambiar puerto en docker-compose.prod.yml
```

### Base de datos no conecta
```bash
# Verificar que el contenedor de DB está corriendo
docker-compose -f docker-compose.yml -f docker-compose.prod.yml ps db

# Ver logs de la base de datos
docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs db
```

## 🔐 Seguridad en Producción

1. **Cambiar todas las contraseñas por defecto**
2. **No exponer puerto 3306 de MySQL** (ya configurado en docker-compose.prod.yml)
3. **Usar HTTPS/SSL**
4. **Configurar firewall** (UFW)
   ```bash
   sudo ufw allow 80/tcp
   sudo ufw allow 443/tcp
   sudo ufw enable
   ```
5. **Backups regulares** de la base de datos
6. **Monitorear logs** regularmente

## 📝 Checklist de Despliegue

- [ ] Docker y Docker Compose instalados
- [ ] Código subido al servidor
- [ ] Archivo `.env` configurado con valores de producción
- [ ] Contraseñas cambiadas y seguras
- [ ] Build completado sin errores
- [ ] Migraciones ejecutadas
- [ ] Cache de Laravel optimizado
- [ ] Aplicación accesible en el navegador
- [ ] SSL/HTTPS configurado (opcional pero recomendado)
- [ ] Backups configurados
- [ ] Monitoreo configurado

## 🆘 Soporte

Si encuentras problemas:
1. Revisa los logs: `docker-compose logs -f`
2. Verifica el estado: `docker-compose ps`
3. Revisa la documentación en `OPTIMIZACIONES.md`
