@echo off
echo ========================================
echo   CREAR USUARIO ADMINISTRADOR
echo ========================================
echo.
echo Este script creara un usuario administrador automaticamente
echo.
echo Email: admin@inventario.com
echo Password: password123
echo.
pause

echo Creando usuario...
docker-compose exec -T app php artisan tinker --execute="\App\Models\User::create(['name' => 'Administrador', 'email' => 'admin@inventario.com', 'password' => bcrypt('password123')]);"

if errorlevel 1 (
    echo.
    echo ERROR: No se pudo crear el usuario.
    echo.
    echo Intenta manualmente:
    echo   docker-compose exec app php artisan tinker
    echo.
    echo Luego ejecuta:
    echo   \App\Models\User::create(['name' => 'Administrador', 'email' => 'admin@inventario.com', 'password' => bcrypt('password123')]);
) else (
    echo.
    echo ========================================
    echo   USUARIO CREADO EXITOSAMENTE
    echo ========================================
    echo.
    echo Credenciales:
    echo   Email: admin@inventario.com
    echo   Password: password123
    echo.
    echo Puedes acceder en: http://localhost:8000
    echo.
)

pause
