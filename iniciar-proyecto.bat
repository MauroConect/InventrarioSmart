@echo off
echo ========================================
echo   INVENTARIO INTELIGENTE - INSTALACION
echo ========================================
echo.

REM Verificar si existe .env
if not exist .env (
    echo [1/7] Creando archivo .env...
    if exist .env.example (
        copy .env.example .env >nul
        echo     Archivo .env creado desde .env.example
    ) else (
        echo     ERROR: No se encuentra .env.example
        pause
        exit /b 1
    )
) else (
    echo [1/7] Archivo .env ya existe, omitiendo...
)
echo.

echo [2/7] Construyendo contenedores Docker (esto puede tardar varios minutos)...
docker-compose up -d --build
if errorlevel 1 (
    echo     ERROR: No se pudieron construir los contenedores
    pause
    exit /b 1
)
echo     Contenedores construidos correctamente
echo.

echo [3/7] Instalando dependencias de PHP (Composer)...
echo     Esto puede tardar varios minutos, por favor sea paciente...
docker-compose exec -T app composer config --global process-timeout 600
docker-compose exec -T app composer install --no-interaction --prefer-dist --no-scripts
docker-compose exec -T app composer dump-autoload --optimize
if errorlevel 1 (
    echo     ERROR: No se pudieron instalar las dependencias de Composer
    echo     Intentando nuevamente con timeout aumentado...
    docker-compose exec -T app composer install --no-interaction --prefer-dist --ignore-platform-reqs
    if errorlevel 1 (
        echo     ERROR: Fallo persistente. Revisa tu conexion a internet.
        pause
        exit /b 1
    )
)
echo     Dependencias de Composer instaladas
echo.

echo [4/7] Generando clave de aplicacion Laravel...
docker-compose exec -T app php artisan key:generate --force
if errorlevel 1 (
    echo     ERROR: No se pudo generar la clave
    pause
    exit /b 1
)
echo     Clave generada correctamente
echo.

echo [5/7] Ejecutando migraciones de base de datos...
timeout /t 5 /nobreak >nul
docker-compose exec -T app php artisan migrate --force
if errorlevel 1 (
    echo     ERROR: No se pudieron ejecutar las migraciones
    pause
    exit /b 1
)
echo     Migraciones ejecutadas correctamente
echo.

echo [6/7] Instalando dependencias de Node.js...
docker-compose exec -T app npm install
if errorlevel 1 (
    echo     ERROR: No se pudieron instalar las dependencias de Node
    pause
    exit /b 1
)
echo     Dependencias de Node instaladas
echo.

echo [7/7] Compilando assets...
docker-compose exec -T app npm run build
if errorlevel 1 (
    echo     ERROR: No se pudieron compilar los assets
    pause
    exit /b 1
)
echo     Assets compilados correctamente
echo.

echo ========================================
echo   INSTALACION COMPLETADA
echo ========================================
echo.
echo IMPORTANTE: Necesitas crear un usuario administrador
echo.
echo Ejecuta el siguiente comando:
echo   docker-compose exec app php artisan tinker
echo.
echo Luego dentro de tinker, ejecuta:
echo   \App\Models\User::create([
echo       'name' => 'Administrador',
echo       'email' => 'admin@inventario.com',
echo       'password' => bcrypt('password123')
echo   ]);
echo.
echo La aplicacion esta disponible en: http://localhost:8000
echo.
pause
