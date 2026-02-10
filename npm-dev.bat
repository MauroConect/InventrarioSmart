@echo off
echo ========================================
echo   EJECUTANDO NPM RUN DEV
echo ========================================
echo.
echo Este comando iniciara Vite en modo desarrollo
echo con hot-reload activado.
echo.
echo IMPORTANTE: Este proceso debe mantenerse corriendo.
echo No cierres esta ventana mientras trabajes en el proyecto.
echo.
echo Presiona Ctrl+C para detener el servidor.
echo.
pause

docker-compose exec app npm run dev
