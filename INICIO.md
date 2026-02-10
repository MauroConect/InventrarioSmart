# 🚀 Guía de Inicio Rápido - Inventario Inteligente

## Prerequisitos
- Docker Desktop instalado y ejecutándose
- Docker Compose instalado

## Paso 1: Crear archivo .env

Copia el archivo de ejemplo y créalo como .env (si no existe):

```bash
# En Windows (PowerShell)
Copy-Item .env.example .env

# O manualmente crea un archivo .env con el contenido del .env.example
```

## Paso 2: Construir y levantar contenedores Docker

```bash
docker-compose up -d --build
```

Esto puede tardar varios minutos la primera vez que descarga las imágenes.

## Paso 3: Instalar dependencias de PHP (Composer)

```bash
docker-compose exec app composer install
```

## Paso 4: Generar clave de aplicación Laravel

```bash
docker-compose exec app php artisan key:generate
```

## Paso 5: Ejecutar migraciones de base de datos

```bash
docker-compose exec app php artisan migrate
```

## Paso 6: Instalar dependencias de Node.js

```bash
docker-compose exec app npm install
```

## Paso 7: Crear un usuario administrador

Ejecuta tinker para crear un usuario:

```bash
docker-compose exec app php artisan tinker
```

Luego dentro de tinker, ejecuta:

```php
\App\Models\User::create([
    'name' => 'Administrador',
    'email' => 'admin@inventario.com',
    'password' => bcrypt('password123')
]);
```

Presiona `Ctrl+C` o escribe `exit` para salir de tinker.

## Paso 8: Compilar assets de desarrollo

Para desarrollo con hot-reload:

```bash
docker-compose exec app npm run dev
```

O para producción:

```bash
docker-compose exec app npm run build
```

## Paso 9: Acceder a la aplicación

Abre tu navegador en: **http://localhost:8000**

### Credenciales de prueba:
- **Email:** admin@inventario.com
- **Contraseña:** password123

## Comandos útiles

### Ver logs de los contenedores:
```bash
docker-compose logs -f
```

### Detener contenedores:
```bash
docker-compose down
```

### Reiniciar contenedores:
```bash
docker-compose restart
```

### Ejecutar comandos Artisan:
```bash
docker-compose exec app php artisan [comando]
```

### Acceder al contenedor:
```bash
docker-compose exec app bash
```

### Verificar estado de contenedores:
```bash
docker-compose ps
```

## Solución de problemas

### Error: Puerto 8000 o 3306 ya en uso
- Detén otros servicios que usen esos puertos
- O cambia los puertos en `docker-compose.yml`

### Error: No se puede conectar a la base de datos
- Espera unos segundos después de levantar los contenedores
- Verifica que el contenedor `inventario_db` esté corriendo: `docker-compose ps`

### Error: Permisos en storage/
```bash
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

### Reinstalar todo desde cero:
```bash
docker-compose down -v
docker-compose up -d --build
docker-compose exec app composer install
docker-compose exec app npm install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate
docker-compose exec app npm run build
```

## Estructura del proyecto

- `app/` - Lógica de aplicación Laravel
- `resources/js/` - Código React
- `database/migrations/` - Migraciones de BD
- `routes/api.php` - Rutas API
- `docker-compose.yml` - Configuración Docker

¡Listo para usar! 🎉
