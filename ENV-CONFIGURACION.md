# 📋 Configuración del Archivo .env

Esta guía explica cómo configurar el archivo `.env` para el proyecto con Blade.

## 🔧 Configuración para Producción

Crea un archivo `.env` en la raíz del proyecto con el siguiente contenido:

```env
# ============================================
# CONFIGURACIÓN DE LA APLICACIÓN
# ============================================
APP_NAME="Inventario Inteligente"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_TIMEZONE=UTC
APP_URL=http://lucianogonzalezautomotores.site
APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_FAKER_LOCALE=es_ES

# ============================================
# CONFIGURACIÓN DE BASE DE DATOS
# ============================================
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=inventario_db
DB_USERNAME=inventario_user
DB_PASSWORD=root

# ============================================
# CONFIGURACIÓN DE SESIONES
# ============================================
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

# ============================================
# CONFIGURACIÓN DE LOGS
# ============================================
LOG_CHANNEL=stack
LOG_LEVEL=error
LOG_DEPRECATIONS_CHANNEL=null

# ============================================
# CONFIGURACIÓN DE CACHE Y QUEUES
# ============================================
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local

# ============================================
# CONFIGURACIÓN DE MAIL (Opcional)
# ============================================
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

# ============================================
# CONFIGURACIÓN DE BROADCASTING (Opcional)
# ============================================
BROADCAST_DRIVER=log

# ============================================
# CONFIGURACIÓN DE SANCTUM (API Auth)
# ============================================
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1,lucianogonzalezautomotores.site
```

## 📝 Explicación de Variables Importantes

### Variables de Aplicación

- **`APP_NAME`**: Nombre de la aplicación
- **`APP_ENV`**: Ambiente (`production` para producción, `local` para desarrollo)
- **`APP_KEY`**: Clave de encriptación (se genera automáticamente con `php artisan key:generate`)
- **`APP_DEBUG`**: **IMPORTANTE**: Debe ser `false` en producción para seguridad
- **`APP_URL`**: URL completa de tu aplicación (sin barra final)
- **`APP_LOCALE`**: Idioma por defecto (`es` para español)

### Variables de Base de Datos

- **`DB_HOST`**: **CRÍTICO**: Debe ser `db` (nombre del servicio en Docker), NO `localhost` ni `127.0.0.1`
- **`DB_PORT`**: **CRÍTICO**: Debe ser `3306` (puerto interno del contenedor), NO `3307` (puerto externo)
- **`DB_DATABASE`**: Nombre de la base de datos
- **`DB_USERNAME`**: Usuario de la base de datos
- **`DB_PASSWORD`**: Contraseña de la base de datos

### Variables de Sesión

- **`SESSION_DRIVER`**: `database` (recomendado para producción)
- **`SESSION_LIFETIME`**: Tiempo de vida de la sesión en minutos (120 = 2 horas)

### Variables de Cache

- **`CACHE_STORE`**: `database` (usa la base de datos como cache)
- **`QUEUE_CONNECTION`**: `database` (usa la base de datos para colas)

## ⚠️ Configuraciones Críticas para Producción

### 1. DB_HOST y DB_PORT
```env
# ✅ CORRECTO
DB_HOST=db
DB_PORT=3306

# ❌ INCORRECTO (causará errores de conexión)
DB_HOST=localhost
DB_HOST=127.0.0.1
DB_PORT=3307
```

**Razón**: Dentro de Docker, los contenedores se comunican por nombre de servicio (`db`), no por `localhost`. El puerto `3306` es el puerto interno del contenedor MySQL.

### 2. APP_DEBUG
```env
# ✅ CORRECTO (Producción)
APP_DEBUG=false

# ❌ INCORRECTO (expondrá información sensible)
APP_DEBUG=true
```

### 3. APP_ENV
```env
# ✅ CORRECTO (Producción)
APP_ENV=production

# ❌ INCORRECTO
APP_ENV=local
```

## 🔄 Configuración para Desarrollo Local

Si estás desarrollando localmente, usa estas configuraciones:

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
```

## 🚀 Pasos para Configurar

### 1. Crear el archivo .env

```bash
# Copiar desde ejemplo (si existe)
cp .env.example .env

# O crear manualmente
touch .env
```

### 2. Agregar las configuraciones

Edita el archivo `.env` y agrega todas las variables mostradas arriba.

### 3. Generar APP_KEY

```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan key:generate
```

Esto generará automáticamente la clave `APP_KEY`.

### 4. Verificar configuración

```bash
# Verificar que las variables estén correctas
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan config:show database
```

## 🔍 Verificar Configuración

### Verificar conexión a la base de datos

```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan tinker
```

Dentro de tinker:
```php
DB::connection()->getPdo();
// Si no hay error, la conexión está correcta
```

### Verificar variables de entorno

```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan config:show app
```

## 🐛 Solución de Problemas Comunes

### Error: "Connection refused" a la base de datos

**Causa**: `DB_HOST` está mal configurado.

**Solución**:
```env
# Cambiar de:
DB_HOST=localhost
# A:
DB_HOST=db
```

Luego limpiar caché:
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan config:clear
```

### Error: "SQLSTATE[HY000] [2002] php_network_getaddresses: getaddrinfo for db failed"

**Causa**: El contenedor `db` no está corriendo o la red Docker no está configurada.

**Solución**:
```bash
# Verificar que el contenedor db esté corriendo
docker-compose -f docker-compose.yml -f docker-compose.prod.yml ps

# Si no está corriendo, levantarlo
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d db

# Esperar a que esté healthy
docker inspect inventario_db --format='{{.State.Health.Status}}'
```

### Error: "APP_KEY is not set"

**Causa**: No se ha generado la clave de aplicación.

**Solución**:
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan key:generate --force
```

## 📋 Checklist de Configuración

Antes de desplegar, verifica:

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `APP_KEY` está configurado (no vacío)
- [ ] `DB_HOST=db` (no `localhost`)
- [ ] `DB_PORT=3306` (no `3307`)
- [ ] `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` están configurados
- [ ] `APP_URL` apunta a tu dominio correcto
- [ ] No hay espacios extra o caracteres especiales en los valores

## 🔐 Seguridad

**NUNCA**:

- ❌ Compartas tu archivo `.env` públicamente
- ❌ Subas `.env` a Git (debe estar en `.gitignore`)
- ❌ Uses `APP_DEBUG=true` en producción
- ❌ Dejes contraseñas por defecto en producción

**SÍ**:

- ✅ Usa contraseñas fuertes en producción
- ✅ Mantén `APP_DEBUG=false` en producción
- ✅ Usa HTTPS en producción (`APP_URL=https://...`)
- ✅ Rota las claves periódicamente
