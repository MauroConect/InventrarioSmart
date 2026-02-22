# 🏪 Sistema de Control de Inventario Inteligente

Sistema de gestión de inventario desarrollado con **Laravel** y **Blade**, dockerizado para fácil despliegue.

## 🚀 Despliegue Rápido

### Opción 1: Script Automático (Recomendado para Linux)

#### 1. Configurar entorno
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

#### 2. Hacer ejecutables los scripts (solo la primera vez)
```bash
chmod +x deploy.sh deploy-produccion.sh crear-usuario-admin.sh
```

#### 3. Desplegar
```bash
# Despliegue completo (producción o desarrollo)
./deploy.sh produccion

# O usar el script rápido de producción
./deploy-produccion.sh
```

#### 4. Crear usuario administrador
```bash
# Usuario por defecto: admin@inventario.com / password123
./crear-usuario-admin.sh

# O personalizado
./crear-usuario-admin.sh tu-email@ejemplo.com tu-password
```

#### 5. Acceder a la aplicación
Abre tu navegador en: **http://localhost:8000**

---

### Opción 2: Despliegue Manual

#### 1. Configurar entorno
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

#### 2. Construir y levantar contenedores
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml build
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

#### 3. Instalar dependencias y configurar
```bash
# Instalar dependencias de Composer
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Ejecutar migraciones
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan migrate --force

# Limpiar cachés
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan config:clear
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan cache:clear
```

#### 4. Crear usuario administrador
```bash
./crear-usuario-admin.sh
```

#### 5. Acceder a la aplicación
Abre tu navegador en: **http://localhost:8000**

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

# Ver logs solo del app
docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs -f app

# Detener contenedores
docker-compose -f docker-compose.yml -f docker-compose.prod.yml down

# Reiniciar contenedores
docker-compose -f docker-compose.yml -f docker-compose.prod.yml restart

# Reiniciar solo el app
docker-compose -f docker-compose.yml -f docker-compose.prod.yml restart app

# Ejecutar comandos artisan
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan <comando>

# Ver estado de contenedores
docker-compose -f docker-compose.yml -f docker-compose.prod.yml ps

# Acceder al shell del contenedor
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app sh
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
# Obtener el ID del contenedor y ejecutar como root
CONTAINER_ID=$(docker-compose -f docker-compose.yml -f docker-compose.prod.yml ps -q app)
docker exec -u root $CONTAINER_ID sh -c "chmod -R 775 /var/www/storage /var/www/bootstrap/cache"
docker exec -u root $CONTAINER_ID sh -c "chown -R www:www /var/www/storage /var/www/bootstrap/cache"
```

### Error: "Connection refused" a la base de datos
Verifica en `.env` que `DB_HOST=db` (no `localhost`) y `DB_PORT=3306` (no `3307`)

### Error: "Unauthenticated" en producción después del login
Este error ocurre cuando las cookies de sesión no se comparten correctamente. Solución:

1. **Ejecutar el script de diagnóstico:**
   ```bash
   chmod +x fix-autenticacion-produccion.sh
   ./fix-autenticacion-produccion.sh
   ```

2. **O manualmente, agregar a tu `.env`:**
   ```env
   # Reemplaza 'tu-dominio.com' con tu dominio real
   SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000,localhost:8000,127.0.0.1,127.0.0.1:8000,::1,tu-dominio.com,www.tu-dominio.com
   
   # Si usas HTTPS:
   SESSION_SECURE_COOKIE=true
   SESSION_SAME_SITE=none
   ```

3. **Limpiar cachés:**
   ```bash
   docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan config:clear
   docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan cache:clear
   docker-compose -f docker-compose.yml -f docker-compose.prod.yml restart app
   ```

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
