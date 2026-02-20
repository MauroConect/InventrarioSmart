# 🔍 Comandos para Diagnosticar Error 500

## 📋 Comandos Rápidos

### 1. Ver logs de Nginx (últimas 50 líneas)
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs --tail=50 webserver
```

### 2. Ver logs de Laravel (últimas 50 líneas)
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs --tail=50 app
```

### 3. Ver archivo de log de Laravel
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app tail -100 /var/www/storage/logs/laravel.log
```

### 4. Ver configuración de Nginx activa
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec webserver cat /etc/nginx/conf.d/default.conf
```

### 5. Verificar sintaxis de Nginx
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec webserver nginx -t
```

### 6. Ver logs de error de Nginx
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec webserver tail -50 /var/log/nginx/error.log
```

### 7. Ver logs en tiempo real
```bash
# Todos los servicios
docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs -f

# Solo Nginx
docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs -f webserver

# Solo Laravel
docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs -f app
```

## 🔧 Diagnóstico Completo

### Script Automático
```bash
chmod +x diagnosticar-error-500.sh
./diagnosticar-error-500.sh
```

## 🐛 Problemas Comunes y Soluciones

### 1. Error: "Permission denied" en storage/logs

**Solución:**
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app sh -c "
    chown -R www:www /var/www/storage /var/www/bootstrap/cache && \
    chmod -R 775 /var/www/storage /var/www/bootstrap/cache && \
    touch /var/www/storage/logs/laravel.log && \
    chmod 664 /var/www/storage/logs/laravel.log
"
```

### 2. Error: "Class not found" o problemas con Composer

**Solución:**
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan config:clear
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan cache:clear
```

### 3. Error: "Connection refused" a la base de datos

**Solución:**
```bash
# Verificar que DB_HOST=db en .env
# Limpiar caché de configuración
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan config:clear

# Verificar conexión
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan tinker --execute="DB::connection()->getPdo();"
```

### 4. Error: "APP_KEY is not set"

**Solución:**
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan key:generate --force
```

### 5. Error: "No application encryption key has been specified"

**Solución:**
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan key:generate --force
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan config:clear
```

### 6. Error: "The stream or file could not be opened"

**Solución:**
```bash
# Arreglar permisos
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app sh -c "
    mkdir -p /var/www/storage/logs && \
    touch /var/www/storage/logs/laravel.log && \
    chown -R www:www /var/www/storage && \
    chmod -R 775 /var/www/storage && \
    chmod 664 /var/www/storage/logs/laravel.log
"
```

## 📊 Verificar Estado de Servicios

### Ver estado de contenedores
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml ps
```

### Verificar que todos estén "Up" y "healthy"
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml ps | grep -E "NAME|inventario"
```

### Reiniciar servicios
```bash
# Reiniciar todos
docker-compose -f docker-compose.yml -f docker-compose.prod.yml restart

# Reiniciar solo Laravel
docker-compose -f docker-compose.yml -f docker-compose.prod.yml restart app

# Reiniciar solo Nginx
docker-compose -f docker-compose.yml -f docker-compose.prod.yml restart webserver
```

## 🔍 Verificar Configuración

### Ver configuración de Laravel
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan config:show
```

### Ver configuración de base de datos
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan config:show database
```

### Limpiar todas las cachés
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan optimize:clear
```

## 📝 Pasos de Diagnóstico Recomendados

1. **Ver logs de Nginx** - Identificar si el error viene de Nginx
2. **Ver logs de Laravel** - Ver el error específico de Laravel
3. **Verificar permisos** - Asegurar que storage tenga permisos correctos
4. **Verificar conexión DB** - Confirmar que la base de datos está accesible
5. **Limpiar cachés** - Limpiar config, cache, view, route
6. **Verificar configuración** - Confirmar que .env está correcto

## 💡 Comandos Útiles Adicionales

### Ver solo errores en los logs
```bash
# Errores de Nginx
docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs webserver | grep -i error

# Errores de Laravel
docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs app | grep -i error

# Errores en el archivo de log
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app grep -i "error\|exception\|fatal" /var/www/storage/logs/laravel.log | tail -20
```

### Probar conexión PHP-FPM
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php -v
```

### Ver variables de entorno del contenedor
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app env | grep -E "APP_|DB_"
```
