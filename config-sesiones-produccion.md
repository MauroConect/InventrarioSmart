# Configuración de Sesiones para Producción

## Variables de entorno necesarias en .env

Para que las sesiones funcionen correctamente en producción, asegúrate de tener estas variables en tu `.env`:

```env
# URL de la aplicación (sin barra final)
APP_URL=http://tu-dominio.com
# O si usas HTTPS:
APP_URL=https://tu-dominio.com

# Configuración de sesiones
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_DOMAIN=.tu-dominio.com  # Con punto al inicio para subdominios
SESSION_SECURE_COOKIE=false    # true solo si usas HTTPS
SESSION_SAME_SITE=lax           # o 'none' si tienes problemas

# Sanctum - Dominios stateful (agregar tu dominio)
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000,localhost:8000,127.0.0.1,127.0.0.1:8000,::1,tu-dominio.com,www.tu-dominio.com
```

## Si usas HTTPS:

```env
APP_URL=https://tu-dominio.com
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=none
```

## Si tienes problemas con cookies:

1. Verifica que `SESSION_DOMAIN` esté configurado correctamente
2. Si usas HTTPS, asegúrate de que `SESSION_SECURE_COOKIE=true`
3. Si las cookies no se comparten entre subdominios, usa `SESSION_DOMAIN=.tu-dominio.com` (con punto)
4. Si aún tienes problemas, prueba con `SESSION_SAME_SITE=none` (requiere HTTPS)
