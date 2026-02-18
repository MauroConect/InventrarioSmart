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
if "%ENVIRONMENT%"=="production" (
    echo [INFO] Usando configuracion de produccion (assets se compilan en el build)...
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
) else (
    echo [INFO] Usando configuracion de desarrollo...
    docker-compose -f docker-compose.dev.yml up -d --build
)
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
if "%ENVIRONMENT%"=="production" (
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
) else (
    docker-compose -f docker-compose.dev.yml exec -T app composer install --no-interaction --prefer-dist --optimize-autoloader
)
if %errorlevel% neq 0 (
    echo [ERROR] Error al instalar dependencias de Composer
    pause
    exit /b 1
)
echo.

REM Generar clave de aplicación
echo [INFO] Generando clave de aplicacion...
if "%ENVIRONMENT%"=="production" (
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan key:generate
) else (
    docker-compose -f docker-compose.dev.yml exec -T app php artisan key:generate
)
echo.

REM Ejecutar migraciones
echo [INFO] Ejecutando migraciones de base de datos...
if "%ENVIRONMENT%"=="production" (
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan migrate --force
) else (
    docker-compose -f docker-compose.dev.yml exec -T app php artisan migrate --force
)
if %errorlevel% neq 0 (
    echo [ERROR] Error al ejecutar migraciones
    pause
    exit /b 1
)
echo.

REM Crear enlace simbólico para storage
echo [INFO] Creando enlace simbolico para storage...
if "%ENVIRONMENT%"=="production" (
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan storage:link
) else (
    docker-compose -f docker-compose.dev.yml exec -T app php artisan storage:link
)
echo.

REM En producción, los assets ya se compilaron durante el build del Dockerfile
REM Solo compilar en desarrollo si es necesario
if not "%ENVIRONMENT%"=="production" (
    echo [INFO] Instalando dependencias de Node.js (solo desarrollo)...
    docker-compose -f docker-compose.dev.yml exec -T app npm install --prefer-offline --no-audit
    if %errorlevel% neq 0 (
        echo [ADVERTENCIA] Error al instalar dependencias de Node.js, continuando...
    )
    echo.
    echo [INFO] Para compilar assets en desarrollo, ejecuta: docker-compose -f docker-compose.dev.yml exec app npm run dev
    echo.
) else (
    echo [INFO] Assets compilados durante el build del Dockerfile (produccion optimizada)
    echo.
)

REM Optimizar Laravel para producción
if "%ENVIRONMENT%"=="production" (
    echo [INFO] Optimizando Laravel para produccion...
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan config:cache
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan route:cache
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan view:cache
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan event:cache
    echo.
)

REM Verificar estado
echo [INFO] Verificando estado de los contenedores...
if "%ENVIRONMENT%"=="production" (
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml ps
) else (
    docker-compose -f docker-compose.dev.yml ps
)
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
