@echo off
echo ========================================
echo Optimizando Laravel para Produccion
echo ========================================
echo.

echo [1/6] Limpiando cache de configuracion...
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan config:clear
echo.

echo [2/6] Limpiando cache de rutas...
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan route:clear
echo.

echo [3/6] Limpiando cache de vistas...
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan view:clear
echo.

echo [4/6] Limpiando cache general...
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan cache:clear
echo.

echo [5/6] Optimizando autoloader de Composer...
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app composer dump-autoload --optimize --no-dev
echo.

echo [6/6] Cacheando configuracion para produccion...
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan config:cache
echo.

echo [7/6] Cacheando rutas para produccion...
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan route:cache
echo.

echo [8/8] Cacheando vistas para produccion...
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan view:cache
echo.

echo ========================================
echo Optimizacion completada!
echo ========================================
pause
