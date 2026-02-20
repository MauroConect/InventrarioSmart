@echo off
REM Script para ver logs y diagnosticar error 500 - Windows

echo.
echo ========================================
echo DIAGNOSTICO DE ERROR 500
echo ========================================
echo.

echo [1] Ver logs de Nginx (ultimas 50 lineas)...
docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs --tail=50 webserver
echo.
echo Presiona cualquier tecla para continuar...
pause >nul

echo.
echo [2] Ver logs de Laravel (ultimas 50 lineas)...
docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs --tail=50 app
echo.
echo Presiona cualquier tecla para continuar...
pause >nul

echo.
echo [3] Ver archivo de log de Laravel...
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app tail -100 /var/www/storage/logs/laravel.log
echo.
echo Presiona cualquier tecla para continuar...
pause >nul

echo.
echo [4] Ver configuracion de Nginx activa...
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec webserver cat /etc/nginx/conf.d/default.conf
echo.
echo Presiona cualquier tecla para continuar...
pause >nul

echo.
echo [5] Verificar sintaxis de Nginx...
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec webserver nginx -t
echo.

pause
