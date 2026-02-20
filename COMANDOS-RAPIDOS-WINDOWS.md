# 🚀 Comandos Rápidos para Diagnosticar Error 500 (Windows)

## 📋 Comandos Más Importantes

### 1. Ver logs de Laravel (el más importante)
```cmd
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app tail -100 /var/www/storage/logs/laravel.log
```

### 2. Ver logs de Nginx
```cmd
docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs --tail=50 webserver
```

### 3. Ver logs de la aplicación
```cmd
docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs --tail=50 app
```

### 4. Ver configuración de Nginx activa
```cmd
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec webserver cat /etc/nginx/conf.d/default.conf
```

### 5. Verificar sintaxis de Nginx
```cmd
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec webserver nginx -t
```

## 🔧 Soluciones Rápidas

### Arreglar permisos (muy común)
```cmd
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app sh -c "chown -R www:www /var/www/storage /var/www/bootstrap/cache && chmod -R 775 /var/www/storage /var/www/bootstrap/cache && touch /var/www/storage/logs/laravel.log && chmod 664 /var/www/storage/logs/laravel.log"
```

### Limpiar cachés
```cmd
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan optimize:clear
```

### Verificar conexión a base de datos
```cmd
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan tinker --execute="DB::connection()->getPdo();"
```

## 📊 Script Automático

Ejecuta:
```cmd
ver-logs.bat
```

Este script te mostrará todos los logs paso a paso.
