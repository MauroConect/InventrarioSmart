# 🏪 Sistema de Control de Inventario Inteligente

Sistema de gestión de inventario desarrollado con **Laravel** y **Blade**, dockerizado para fácil despliegue.

## 🚀 Despliegue Rápido

### 1. Configurar entorno
```bash
cp .env.example .env
```

Edita `.env` y asegúrate de que tenga:
```env
DB_HOST=db
DB_PORT=3306
DB_DATABASE=inventario_db
DB_USERNAME=inventario_user
DB_PASSWORD=root
APP_DEBUG=false
```

### 2. Construir y levantar contenedores
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml build
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

### 3. Instalar dependencias y configurar
```bash
# Instalar dependencias de Composer
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Crear .env en el contenedor si no existe
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app sh -c "if [ ! -f /var/www/.env ]; then cp /var/www/.env.example /var/www/.env 2>/dev/null || touch /var/www/.env; fi"

# Generar clave de aplicación
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan key:generate --force

# Copiar .env del contenedor al host (para sincronizar)
docker cp inventario_app:/var/www/.env .env

# Ejecutar migraciones
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan migrate --force

# Crear enlace simbólico de storage
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan storage:link

# Limpiar cachés
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan config:clear
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan cache:clear
```

### 4. Crear usuario administrador
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan tinker
```

Dentro de tinker (ejecuta código PHP, NO comandos artisan):
```php
\App\Models\User::create([
    'name' => 'Administrador',
    'email' => 'admin@inventario.com',
    'password' => bcrypt('password123')
]);
```

Para salir de tinker: escribe `exit` o presiona `Ctrl+C`

### 5. Acceder a la aplicación
Abre tu navegador en: **http://localhost:8000**

**Credenciales:**
- Email: `admin@inventario.com`
- Password: `password123`

## 🔧 Acceder al Contenedor

### Entrar al shell del contenedor
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app sh
```

### Ejecutar comandos artisan (FUERA de tinker)
```bash
# Limpiar cachés
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan config:clear
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan cache:clear

# Ejecutar migraciones
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan migrate

# Ver rutas
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan route:list
```

### Usar Tinker (para código PHP)
```bash
# Entrar a tinker
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan tinker

# Dentro de tinker puedes ejecutar código PHP:
# \App\Models\User::all()
# \App\Models\User::where('email', 'admin@inventario.com')->first()
# exit  (para salir)
```

**IMPORTANTE**: Tinker es para código PHP, NO para comandos artisan. Para comandos artisan, sal de tinker y ejecútalos directamente.

## 📋 Comandos Útiles

```bash
# Ver logs
docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs -f

# Detener contenedores
docker-compose -f docker-compose.yml -f docker-compose.prod.yml down

# Reiniciar contenedores
docker-compose -f docker-compose.yml -f docker-compose.prod.yml restart

# Ejecutar comandos artisan
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan <comando>
```

## 🔧 Solución de Problemas

### Error 500: "No application encryption key has been specified"
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan key:generate --force
docker cp inventario_app:/var/www/.env .env
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan config:clear
docker-compose -f docker-compose.yml -f docker-compose.prod.yml restart app
```

### Error: "vendor/autoload.php not found"
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
```

### Error: "Permission denied" en storage
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app sh -c "chown -R www:www /var/www/storage /var/www/bootstrap/cache && chmod -R 775 /var/www/storage /var/www/bootstrap/cache"
```

### Error: "Connection refused" a la base de datos
Verifica en `.env` que `DB_HOST=db` (no `localhost`) y `DB_PORT=3306` (no `3307`)

## 📦 Requisitos

- Docker
- Docker Compose

## 🎯 Características

- ✅ Gestión de Productos y Categorías
- ✅ Control de Stock
- ✅ Ventas con múltiples formas de pago
- ✅ Gestión de Clientes y Proveedores
- ✅ Apertura y Cierre de Cajas
- ✅ Cuentas Corrientes
- ✅ Gestión de Cheques
- ✅ Dashboard con estadísticas
