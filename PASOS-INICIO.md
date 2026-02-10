# 📋 Pasos para Iniciar el Proyecto

## Opción 1: Usar el Script Automático (Recomendado)

1. **Ejecuta el script de inicio:**
   ```
   iniciar-proyecto.bat
   ```
   
   Este script hará todo automáticamente. Al finalizar, ejecuta:
   ```
   crear-usuario.bat
   ```

## Opción 2: Pasos Manuales

### 1. Crear archivo .env

Crea un archivo `.env` en la raíz del proyecto con este contenido:

```env
APP_NAME="Inventario Inteligente"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_URL=http://localhost

APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_FAKER_LOCALE=es_ES

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=inventario_db
DB_USERNAME=inventario_user
DB_PASSWORD=root

SESSION_DRIVER=database
SESSION_LIFETIME=120

LOG_CHANNEL=stack
LOG_LEVEL=debug

FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
CACHE_STORE=database
```

### 2. Construir y levantar contenedores Docker

```bash
docker-compose up -d --build
```

**Espera 1-2 minutos** para que los contenedores se inicien completamente.

### 3. Instalar dependencias de PHP

```bash
docker-compose exec app composer install
```

### 4. Generar clave de aplicación

```bash
docker-compose exec app php artisan key:generate
```

### 5. Ejecutar migraciones

```bash
docker-compose exec app php artisan migrate
```

### 6. Instalar dependencias de Node.js

```bash
docker-compose exec app npm install
```

### 7. Compilar assets

```bash
docker-compose exec app npm run build
```

### 8. Crear usuario administrador

```bash
docker-compose exec app php artisan tinker
```

Dentro de tinker, ejecuta:

```php
\App\Models\User::create([
    'name' => 'Administrador',
    'email' => 'admin@inventario.com',
    'password' => bcrypt('password123')
]);
```

Presiona `Ctrl+C` o escribe `exit` para salir.

### 9. Acceder a la aplicación

Abre tu navegador en: **http://localhost:8000**

**Credenciales:**
- Email: `admin@inventario.com`
- Password: `password123`

## Verificar que todo funciona

```bash
docker-compose ps
```

Todos los contenedores deberían estar "Up" y "healthy".

## Comandos útiles

- **Ver logs:** `docker-compose logs -f`
- **Detener:** `docker-compose down`
- **Reiniciar:** `docker-compose restart`
- **Modo desarrollo (hot-reload):** `docker-compose exec app npm run dev`

## Solución de problemas

### Error: Puerto en uso
Si el puerto 8000 o 3306 está en uso, edita `docker-compose.yml` y cambia los puertos.

### Error: Base de datos no conecta
Espera unos segundos más después de `docker-compose up` y verifica con:
```bash
docker-compose ps
```

### Reinstalar desde cero
```bash
docker-compose down -v
docker-compose up -d --build
# Luego repite los pasos 3-8
```
