# 🚀 Guía de Despliegue - Inventario Inteligente

Esta guía te ayudará a desplegar el proyecto **Inventario Inteligente** de manera rápida y sencilla usando Docker.

## 📋 Requisitos Previos

Antes de comenzar, asegúrate de tener instalado:

- **Docker Desktop** (versión 20.10 o superior)
- **Docker Compose** (versión 2.0 o superior)
- Al menos **4GB de RAM** disponibles
- **10GB de espacio** en disco

### Verificar Instalación

**Windows (PowerShell):**
```powershell
docker --version
docker-compose --version
```

**Linux/Mac:**
```bash
docker --version
docker-compose --version
```

## 🎯 Opción 1: Despliegue Automatizado (Recomendado)

### Windows

1. Ejecuta el script de despliegue:
   ```cmd
   deploy.bat
   ```

   Para producción:
   ```cmd
   deploy.bat production
   ```

### Linux/Mac

1. Dar permisos de ejecución al script:
   ```bash
   chmod +x deploy.sh
   ```

2. Ejecutar el script:
   ```bash
   ./deploy.sh
   ```

   Para producción:
   ```bash
   ./deploy.sh production
   ```

El script automatizará todo el proceso:
- ✅ Creación del archivo `.env` si no existe
- ✅ Construcción de contenedores Docker
- ✅ Instalación de dependencias (Composer y NPM)
- ✅ Generación de clave de aplicación
- ✅ Ejecución de migraciones
- ✅ Compilación de assets
- ✅ Optimización para producción (si aplica)

## 🔧 Opción 2: Despliegue Manual

### Paso 1: Configurar Variables de Entorno

1. Copia el archivo de ejemplo:
   ```bash
   cp .env.example .env
   ```

2. Edita el archivo `.env` con tus configuraciones:
   ```env
   APP_NAME="Inventario Inteligente"
   APP_ENV=local
   APP_KEY=
   APP_DEBUG=true
   APP_URL=http://localhost:8000
   
   DB_CONNECTION=mysql
   DB_HOST=db
   DB_PORT=3306
   DB_DATABASE=inventario_db
   DB_USERNAME=inventario_user
   DB_PASSWORD=root
   ```

### Paso 2: Construir y Levantar Contenedores

```bash
docker-compose up -d --build
```

Espera 1-2 minutos para que los contenedores se inicien completamente.

### Paso 3: Instalar Dependencias de PHP

```bash
docker-compose exec app composer install
```

### Paso 4: Generar Clave de Aplicación

```bash
docker-compose exec app php artisan key:generate
```

### Paso 5: Ejecutar Migraciones

```bash
docker-compose exec app php artisan migrate
```

### Paso 6: Crear Enlace de Storage

```bash
docker-compose exec app php artisan storage:link
```

### Paso 7: Instalar Dependencias de Node.js

```bash
docker-compose exec app npm install
```

### Paso 8: Compilar Assets

```bash
docker-compose exec app npm run build
```

### Paso 9: (Opcional) Crear Usuario Administrador

```bash
docker-compose exec app php artisan tinker
```

Dentro de tinker:
```php
\App\Models\User::create([
    'name' => 'Administrador',
    'email' => 'admin@inventario.com',
    'password' => bcrypt('password123')
]);
```

Presiona `Ctrl+C` o escribe `exit` para salir.

## 🌐 Acceder a la Aplicación

Una vez completado el despliegue:

- **Frontend/Backend:** http://localhost:8000
- **Base de Datos:** localhost:3307
  - Usuario: `inventario_user`
  - Contraseña: `root`
  - Base de datos: `inventario_db`

**Credenciales por defecto:**
- Email: `admin@inventario.com`
- Contraseña: `password123`

> ⚠️ **IMPORTANTE:** Cambia estas credenciales en producción.

## 📦 Estructura de Contenedores

El proyecto utiliza los siguientes contenedores:

- **`inventario_app`**: Contenedor PHP-FPM con Laravel
- **`inventario_webserver`**: Servidor Nginx
- **`inventario_db`**: Base de datos MySQL 8.0

## 🛠️ Comandos Útiles

### Ver Logs
```bash
docker-compose logs -f
```

### Ver logs de un servicio específico
```bash
docker-compose logs -f app
docker-compose logs -f webserver
docker-compose logs -f db
```

### Detener Contenedores
```bash
docker-compose down
```

### Detener y Eliminar Volúmenes
```bash
docker-compose down -v
```

### Reiniciar Contenedores
```bash
docker-compose restart
```

### Ejecutar Comandos Artisan
```bash
docker-compose exec app php artisan [comando]
```

### Acceder al Contenedor
```bash
docker-compose exec app bash
```

### Ver Estado de Contenedores
```bash
docker-compose ps
```

## 🔄 Actualizar el Proyecto

Cuando actualices el código:

1. **Detener contenedores:**
   ```bash
   docker-compose down
   ```

2. **Actualizar código** (git pull, etc.)

3. **Reconstruir y levantar:**
   ```bash
   docker-compose up -d --build
   ```

4. **Ejecutar migraciones nuevas:**
   ```bash
   docker-compose exec app php artisan migrate
   ```

5. **Recompilar assets:**
   ```bash
   docker-compose exec app npm run build
   ```

## 🚀 Despliegue en Producción

### Configuraciones Recomendadas

1. **Editar `.env` para producción:**
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://tudominio.com
   
   DB_PASSWORD=contraseña_segura
   MYSQL_ROOT_PASSWORD=contraseña_segura_root
   ```

2. **Ejecutar despliegue en modo producción:**
   ```bash
   ./deploy.sh production
   # o
   deploy.bat production
   ```

3. **Configurar SSL/HTTPS** (usando un proxy reverso como Nginx o Traefik)

4. **Configurar backups** de la base de datos:
   ```bash
   docker-compose exec db mysqldump -u root -proot inventario_db > backup.sql
   ```

### Optimizaciones de Producción

El script de despliegue en modo producción ejecuta automáticamente:
- `php artisan config:cache`
- `php artisan route:cache`
- `php artisan view:cache`

## 🐛 Solución de Problemas

### Error: Puerto 8000 ya en uso

Cambia el puerto en `docker-compose.yml`:
```yaml
webserver:
  ports:
    - "8080:80"  # Cambia 8000 por 8080
```

### Error: Puerto 3307 ya en uso

Cambia el puerto en `docker-compose.yml`:
```yaml
db:
  ports:
    - "3308:3306"  # Cambia 3307 por 3308
```

### Error: Permisos de Storage

```bash
docker-compose exec app chmod -R 775 storage bootstrap/cache
docker-compose exec app chown -R www:www storage bootstrap/cache
```

### Error: Base de datos no conecta

1. Verifica que el contenedor de DB esté corriendo:
   ```bash
   docker-compose ps
   ```

2. Verifica los logs:
   ```bash
   docker-compose logs db
   ```

3. Espera unos segundos más (la DB puede tardar en iniciar)

### Limpiar Todo y Empezar de Nuevo

```bash
docker-compose down -v
docker system prune -a
./deploy.sh
```

## 📝 Notas Importantes

- Los datos de la base de datos se persisten en un volumen Docker llamado `dbdata`
- Los archivos subidos se guardan en `storage/app/public`
- Los logs de Laravel están en `storage/logs`
- El archivo `.env` nunca debe subirse al repositorio (está en `.gitignore`)

## 🔐 Seguridad

Para producción, asegúrate de:

1. ✅ Cambiar todas las contraseñas por defecto
2. ✅ Configurar `APP_DEBUG=false`
3. ✅ Usar HTTPS
4. ✅ Configurar firewall
5. ✅ Realizar backups regulares
6. ✅ Mantener Docker y las imágenes actualizadas

## 📞 Soporte

Si encuentras problemas durante el despliegue:

1. Revisa los logs: `docker-compose logs -f`
2. Verifica que todos los requisitos estén instalados
3. Asegúrate de tener suficiente espacio en disco y RAM
4. Revisa que los puertos no estén en uso

---

**¡Listo!** Tu aplicación debería estar funcionando en http://localhost:8000 🎉
