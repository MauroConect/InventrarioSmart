# Solución: Error de Permisos en /var/www/vendor

## Problema
```
/var/www/vendor does not exist and could not be created
```

## Causa
El usuario `www` no tiene permisos para crear directorios en `/var/www` porque los permisos no se establecieron correctamente antes de cambiar al usuario.

## Solución Aplicada

Se modificó el Dockerfile para:
1. Copiar archivos como root
2. Establecer permisos correctos (`chown` y `chmod`)
3. Luego cambiar al usuario `www`

## Si el problema persiste

### Opción 1: Reconstruir la imagen

```bash
# Reconstruir sin caché
docker-compose -f docker-compose.yml -f docker-compose.prod.yml build --no-cache app

# O desplegar completo
./deploy-prod.sh
```

### Opción 2: Arreglar permisos manualmente (temporal)

Si necesitas una solución rápida sin reconstruir:

```bash
# Entrar al contenedor como root
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -u root app sh

# Dentro del contenedor:
chown -R www:www /var/www
chmod -R 755 /var/www
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Salir
exit

# Reintentar composer install
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app composer install --no-dev --optimize-autoloader
```

### Opción 3: Verificar permisos actuales

```bash
# Ver permisos dentro del contenedor
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app ls -la /var/www

# Verificar usuario actual
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app whoami
```

## Verificación

Después de aplicar la solución:

```bash
# Verificar que vendor existe y tiene permisos correctos
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app ls -la /var/www/vendor

# Debería mostrar algo como:
# drwxr-xr-x  www www ...
```

## Nota

El Dockerfile ahora establece permisos correctos automáticamente durante el build, por lo que este problema no debería ocurrir en futuros builds.
