@echo off
echo ========================================
echo Limpiando todas las caches de Laravel
echo ========================================
echo.

echo Limpiando cache de configuracion...
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan config:clear
echo.

echo Limpiando cache de rutas...
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan route:clear
echo.

echo Limpiando cache de vistas...
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan view:clear
echo.

echo Limpiando cache general...
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan cache:clear
echo.

echo ========================================
echo Limpieza completada!
echo ========================================
pause
