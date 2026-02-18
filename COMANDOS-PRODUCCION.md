# 🚀 Comandos Rápidos para Producción - Linux

## Despliegue Inicial

```bash
# 1. Dar permisos de ejecución
chmod +x deploy-prod.sh deploy.sh

# 2. Desplegar (método más simple)
./deploy-prod.sh

# O usando el script completo
./deploy.sh production
```

## Comandos Esenciales

### Ver estado
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml ps
```

### Ver logs
```bash
# Todos los servicios
docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs -f

# Solo la app
docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs -f app

# Últimas 100 líneas
docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs --tail=100
```

### Ver consumo de recursos
```bash
docker stats
```

### Detener
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml down
```

### Reiniciar
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml restart
```

### Reconstruir (después de cambios)
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
```

## Comandos Laravel

### Ejecutar migraciones
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app \
    php artisan migrate --force
```

### Limpiar cache
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app \
    php artisan config:clear && \
    php artisan cache:clear && \
    php artisan view:clear && \
    php artisan route:clear
```

### Reoptimizar (después de limpiar)
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache && \
    php artisan event:cache
```

### Ver versión de Laravel
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app \
    php artisan --version
```

## Base de Datos

### Backup
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec db \
    mysqldump -u root -p${MYSQL_ROOT_PASSWORD} inventario_db > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Restaurar
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T db \
    mysql -u root -p${MYSQL_ROOT_PASSWORD} inventario_db < backup.sql
```

### Acceder a MySQL
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec db \
    mysql -u root -p${MYSQL_ROOT_PASSWORD} inventario_db
```

## Actualizar Aplicación

```bash
# 1. Detener
docker-compose -f docker-compose.yml -f docker-compose.prod.yml down

# 2. Actualizar código (si usas Git)
git pull origin main

# 3. Reconstruir y levantar
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build

# 4. Ejecutar migraciones (si hay nuevas)
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan migrate --force

# 5. Limpiar y reoptimizar
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan config:clear && \
    php artisan cache:clear && \
    php artisan view:clear && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache
```

## Acceder al Contenedor

```bash
# Entrar al contenedor de la app
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app bash

# Entrar al contenedor de la base de datos
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec db bash
```

## Verificar que Todo Funciona

```bash
# Verificar que los contenedores están corriendo
docker ps

# Verificar que la app responde
curl http://localhost

# Verificar logs sin errores
docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs app | grep -i error
```

## Limpiar Recursos

```bash
# Ver uso de disco
docker system df

# Limpiar imágenes no usadas (cuidado)
docker image prune -a

# Limpiar todo (MUY CUIDADO - elimina contenedores detenidos, redes, etc.)
docker system prune -a
```

## Troubleshooting

### El contenedor no inicia
```bash
# Ver logs detallados
docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs app

# Verificar configuración
docker-compose -f docker-compose.yml -f docker-compose.prod.yml config
```

### Error de permisos
```bash
# Ajustar permisos
sudo chown -R $USER:$USER storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

### Puerto 80 ya en uso
```bash
# Ver qué está usando el puerto
sudo netstat -tulpn | grep :80

# O usar lsof
sudo lsof -i :80
```
