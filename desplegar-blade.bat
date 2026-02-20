@echo off
REM Script para desplegar el proyecto con Blade (sin React/Vite) - Windows
REM Uso: desplegar-blade.bat

echo.
echo ========================================
echo DESPLIEGUE CON BLADE
echo ========================================
echo.

REM 1. Detener contenedores
echo [1/10] Deteniendo contenedores existentes...
docker-compose -f docker-compose.yml -f docker-compose.prod.yml down 2>nul
echo [OK] Contenedores detenidos
echo.

REM 2. Verificar .env
echo [2/10] Verificando archivo .env...
if not exist .env (
    if exist .env.example (
        copy .env.example .env >nul
        echo [OK] Creado .env desde .env.example
    ) else (
        echo [ERROR] No se encontro .env.example
        exit /b 1
    )
)
echo [OK] Archivo .env verificado
echo.

REM 3. Construir imágenes
echo [3/10] Construyendo imagenes Docker...
echo Nota: No se compilaran assets de React (usando Blade)
docker-compose -f docker-compose.yml -f docker-compose.prod.yml build --no-cache
if errorlevel 1 (
    echo [ERROR] Error al construir imagenes
    exit /b 1
)
echo [OK] Imagenes construidas
echo.

REM 4. Levantar contenedores
echo [4/10] Levantando contenedores...
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d
if errorlevel 1 (
    echo [ERROR] Error al levantar contenedores
    exit /b 1
)
echo [OK] Contenedores levantados
echo.

REM 5. Esperar base de datos
echo [5/10] Esperando a que la base de datos este lista...
timeout /t 10 /nobreak >nul
echo [OK] Espera completada
echo.

REM 6. Arreglar permisos (en Windows esto puede no funcionar igual)
echo [6/10] Configurando permisos...
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app sh -c "chown -R www:www /var/www/storage /var/www/bootstrap/cache && chmod -R 775 /var/www/storage /var/www/bootstrap/cache && touch /var/www/storage/logs/laravel.log && chmod 664 /var/www/storage/logs/laravel.log" 2>nul
echo [OK] Permisos configurados
echo.

REM 7. Instalar dependencias Composer
echo [7/10] Instalando dependencias de Composer...
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
if errorlevel 1 (
    echo [ERROR] Error al instalar dependencias
    exit /b 1
)
echo [OK] Dependencias instaladas
echo.

REM 8. Configurar Laravel
echo [8/10] Configurando Laravel...
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan key:generate --force 2>nul
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan config:clear
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan cache:clear
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan view:clear
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan route:clear
echo [OK] Laravel configurado
echo.

REM 9. Ejecutar migraciones
echo [9/10] Ejecutando migraciones...
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan migrate --force
if errorlevel 1 (
    echo [ERROR] Error al ejecutar migraciones
    exit /b 1
)
echo [OK] Migraciones ejecutadas
echo.

REM 10. Crear enlace storage
echo [10/10] Creando enlace simbólico de storage...
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan storage:link 2>nul
echo [OK] Enlace creado
echo.

echo ========================================
echo [OK] DESPLIEGUE COMPLETADO
echo ========================================
echo.
echo Proximos pasos:
echo   1. Verificar contenedores: docker-compose -f docker-compose.yml -f docker-compose.prod.yml ps
echo   2. Ver logs: docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs app
echo   3. Crear usuario admin: docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan tinker
echo   4. Acceder: http://localhost:8000
echo.

pause
