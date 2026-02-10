@echo off
REM Script de despliegue automatizado para Inventario Inteligente (Windows)
REM Uso: deploy.bat [production|development]

setlocal enabledelayedexpansion

set ENVIRONMENT=%1
if "%ENVIRONMENT%"=="" set ENVIRONMENT=development

echo.
echo ================================================
echo   DESPLIEGUE INVENTARIO INTELIGENTE
echo   Modo: %ENVIRONMENT%
echo ================================================
echo.

REM Verificar Docker
where docker >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Docker no esta instalado. Por favor instala Docker Desktop.
    pause
    exit /b 1
)

where docker-compose >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Docker Compose no esta instalado.
    pause
    exit /b 1
)

echo [OK] Docker y Docker Compose estan instalados
echo.

REM Verificar archivo .env
if not exist .env (
    echo [ADVERTENCIA] Archivo .env no encontrado.
    if exist .env.example (
        echo [INFO] Creando .env desde .env.example...
        copy .env.example .env >nul
        echo [OK] Archivo .env creado
        echo.
        echo ================================================
        echo   IMPORTANTE: Edita el archivo .env con tus
        echo   configuraciones antes de continuar.
        echo ================================================
        echo.
        pause
    ) else (
        echo [ERROR] No se encontro .env.example
        echo [ERROR] Por favor crea un archivo .env manualmente
        pause
        exit /b 1
    )
)

REM Detener contenedores existentes
echo [INFO] Deteniendo contenedores existentes...
docker-compose down
echo.

REM Construir y levantar contenedores
echo [INFO] Construyendo y levantando contenedores Docker...
docker-compose up -d --build
if %errorlevel% neq 0 (
    echo [ERROR] Error al construir o levantar contenedores
    pause
    exit /b 1
)
echo.

REM Esperar a que la base de datos esté lista
echo [INFO] Esperando a que la base de datos este lista...
timeout /t 15 /nobreak >nul
echo.

REM Instalar dependencias de Composer
echo [INFO] Instalando dependencias de PHP (Composer)...
docker-compose exec -T app composer install --no-interaction --prefer-dist --optimize-autoloader
if %errorlevel% neq 0 (
    echo [ERROR] Error al instalar dependencias de Composer
    pause
    exit /b 1
)
echo.

REM Generar clave de aplicación
echo [INFO] Generando clave de aplicacion...
docker-compose exec -T app php artisan key:generate
echo.

REM Ejecutar migraciones
echo [INFO] Ejecutando migraciones de base de datos...
docker-compose exec -T app php artisan migrate --force
if %errorlevel% neq 0 (
    echo [ERROR] Error al ejecutar migraciones
    pause
    exit /b 1
)
echo.

REM Crear enlace simbólico para storage
echo [INFO] Creando enlace simbolico para storage...
docker-compose exec -T app php artisan storage:link
echo.

REM Instalar dependencias de Node.js
echo [INFO] Instalando dependencias de Node.js...
docker-compose exec -T app npm install
if %errorlevel% neq 0 (
    echo [ERROR] Error al instalar dependencias de Node.js
    pause
    exit /b 1
)
echo.

REM Compilar assets
echo [INFO] Compilando assets...
docker-compose exec -T app npm run build
if %errorlevel% neq 0 (
    echo [ERROR] Error al compilar assets
    pause
    exit /b 1
)
echo.

REM Optimizar Laravel para producción
if "%ENVIRONMENT%"=="production" (
    echo [INFO] Optimizando Laravel para produccion...
    docker-compose exec -T app php artisan config:cache
    docker-compose exec -T app php artisan route:cache
    docker-compose exec -T app php artisan view:cache
    echo.
)

REM Verificar estado
echo [INFO] Verificando estado de los contenedores...
docker-compose ps
echo.

echo ================================================
echo   DESPLIEGUE COMPLETADO EXITOSAMENTE
echo ================================================
echo.
echo Aplicacion disponible en: http://localhost:8000
echo Base de datos disponible en: localhost:3307
echo.
echo Para ver los logs: docker-compose logs -f
echo Para detener: docker-compose down
echo.
pause
