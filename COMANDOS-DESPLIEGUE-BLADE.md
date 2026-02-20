# 🚀 Comandos para Desplegar con Blade

Este documento contiene los comandos para desplegar el proyecto usando Blade en lugar de React.

## 📋 Opción 1: Script Automático (Recomendado)

### Linux/Mac:
```bash
chmod +x desplegar-blade.sh
./desplegar-blade.sh
```

### Windows:
```cmd
desplegar-blade.bat
```

## 📋 Opción 2: Comandos Manuales

### 1. Detener contenedores existentes
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml down
```

### 2. Verificar/corregir archivo .env
```bash
# Si no existe, copiar desde ejemplo
cp .env.example .env

# Corregir configuraciones importantes
sed -i 's/^DB_HOST=.*/DB_HOST=db/' .env
sed -i 's/^DB_PORT=.*/DB_PORT=3306/' .env
sed -i '/^APP_DEBUG=/d' .env
echo "APP_DEBUG=false" >> .env
```

### 3. Construir imágenes Docker
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml build --no-cache
```

### 4. Levantar contenedores
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

### 5. Esperar a que la base de datos esté lista
```bash
# Verificar estado
docker inspect inventario_db --format='{{.State.Health.Status}}'
# Debe mostrar "healthy"
```

### 6. Arreglar permisos
```bash
# Dentro del contenedor
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app sh -c "
    chown -R www:www /var/www/storage /var/www/bootstrap/cache && \
    chmod -R 775 /var/www/storage /var/www/bootstrap/cache && \
    touch /var/www/storage/logs/laravel.log && \
    chmod 664 /var/www/storage/logs/laravel.log
"

# En el host (si los volúmenes están montados)
chmod -R 775 storage bootstrap/cache
chmod 664 storage/logs/laravel.log
```

### 7. Instalar dependencias de Composer
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
```

### 8. Configurar Laravel
```bash
# Generar clave de aplicación (si no existe)
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan key:generate --force

# Limpiar cachés
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan config:clear
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan cache:clear
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan view:clear
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan route:clear
```

### 9. Ejecutar migraciones
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan migrate --force
```

### 10. Crear enlace simbólico de storage
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan storage:link
```

## 🔍 Verificación

### Verificar estado de contenedores
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml ps
```

### Ver logs
```bash
# Todos los servicios
docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs

# Solo la aplicación
docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs app

# Seguir logs en tiempo real
docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs -f app
```

### Verificar conexión a la base de datos
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan tinker
# Luego ejecutar:
# DB::connection()->getPdo();
```

## 👤 Crear Usuario Administrador

```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan tinker
```

Dentro de tinker:
```php
\App\Models\User::create([
    'name' => 'Administrador',
    'email' => 'admin@inventario.com',
    'password' => bcrypt('password123')
]);
```

O usando un script PHP:
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php -r "
require '/var/www/vendor/autoload.php';
\$app = require_once '/var/www/bootstrap/app.php';
\$kernel = \$app->make(\Illuminate\Contracts\Console\Kernel::class);
\$kernel->bootstrap();
\$user = \App\Models\User::where('email', 'admin@inventario.com')->first();
if (\$user) {
    \$user->password = \Illuminate\Support\Facades\Hash::make('password123');
    \$user->save();
    echo 'Usuario actualizado\n';
} else {
    \App\Models\User::create([
        'name' => 'Administrador',
        'email' => 'admin@inventario.com',
        'password' => \Illuminate\Support\Facades\Hash::make('password123')
    ]);
    echo 'Usuario creado\n';
}
echo 'Email: admin@inventario.com\n';
echo 'Password: password123\n';
"
```

## 🔄 Actualizar después de cambios

Si haces cambios en el código y quieres actualizar sin reconstruir todo:

```bash
# 1. Limpiar cachés
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan config:clear
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan cache:clear
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan view:clear

# 2. Si agregaste nuevas migraciones
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan migrate --force

# 3. Si cambiaste dependencias de Composer
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
```

## ⚠️ Notas Importantes

1. **No se compilan assets de React**: Como ahora usamos Blade, no necesitas ejecutar `npm run build` ni tener Node.js en producción.

2. **Permisos**: Asegúrate de que los directorios `storage` y `bootstrap/cache` tengan permisos correctos (775).

3. **Base de datos**: El script pregunta si quieres eliminar la base de datos. Si quieres conservar los datos, responde 'n'.

4. **Variables de entorno**: Verifica que `.env` tenga las configuraciones correctas, especialmente:
   - `DB_HOST=db` (no `127.0.0.1` ni `localhost`)
   - `DB_PORT=3306` (no `3307`)
   - `APP_DEBUG=false`
   - `APP_ENV=production`

5. **Puerto**: La aplicación estará disponible en el puerto configurado en `docker-compose.prod.yml` (generalmente 8000).

## 🐛 Solución de Problemas

### Error: "Container is unhealthy"
```bash
# Reiniciar contenedores en orden
docker-compose -f docker-compose.yml -f docker-compose.prod.yml restart db
sleep 10
docker-compose -f docker-compose.yml -f docker-compose.prod.yml restart app webserver
```

### Error: "Permission denied" en storage
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app sh -c "
    chown -R www:www /var/www/storage /var/www/bootstrap/cache && \
    chmod -R 775 /var/www/storage /var/www/bootstrap/cache
"
```

### Error: "Class not found" o problemas con Composer
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app composer dump-autoload
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan config:clear
```

### Error: "Connection refused" a la base de datos
```bash
# Verificar que DB_HOST=db en .env
# Limpiar caché de configuración
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan config:clear
```
