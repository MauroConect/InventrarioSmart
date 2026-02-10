@echo off
echo ========================================
echo   INSTALACION MANUAL DE COMPOSER
echo ========================================
echo.
echo Este script instala las dependencias con timeout aumentado
echo y manejo mejorado de errores de conexion.
echo.
pause

echo [1/4] Configurando Composer con timeout aumentado...
docker-compose exec -T app composer config --global process-timeout 600
docker-compose exec -T app composer config --global preferred-install dist

echo [2/4] Limpiando cache de Composer...
docker-compose exec -T app composer clear-cache

echo [3/4] Instalando dependencias (esto puede tardar 10-15 minutos)...
echo     Por favor, se paciente. La conexion a GitHub puede ser lenta...
docker-compose exec -T app composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts

if errorlevel 1 (
    echo.
    echo [4/4] Primera instalacion fallo. Reintentando con opciones adicionales...
    docker-compose exec -T app composer install --no-interaction --prefer-dist --ignore-platform-reqs --no-scripts
    if errorlevel 1 (
        echo.
        echo ERROR: La instalacion fallo despues de varios intentos.
        echo.
        echo Posibles soluciones:
        echo 1. Verifica tu conexion a internet
        echo 2. Intenta ejecutar manualmente:
        echo    docker-compose exec app composer install --prefer-dist
        echo 3. Si el problema persiste, verifica tu firewall/proxy
        echo.
        pause
        exit /b 1
    )
)

echo [4/4] Regenerando autoloader optimizado...
docker-compose exec -T app composer dump-autoload --optimize

echo.
echo ========================================
echo   DEPENDENCIAS INSTALADAS EXITOSAMENTE
echo ========================================
echo.
pause
