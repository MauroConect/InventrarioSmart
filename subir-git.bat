@echo off
REM Script para subir el proyecto a Git
REM Uso: subir-git.bat

echo.
echo ================================================
echo   SUBIR PROYECTO A GIT
echo ================================================
echo.

REM Verificar si Git está instalado
where git >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Git no esta instalado.
    echo Por favor instala Git desde: https://git-scm.com/downloads
    pause
    exit /b 1
)

echo [OK] Git esta instalado
echo.

REM Verificar si ya hay un repositorio remoto configurado
git remote -v >nul 2>&1
if %errorlevel% equ 0 (
    echo [INFO] Repositorio remoto ya configurado:
    git remote -v
    echo.
    set /p continuar="¿Deseas continuar con el commit y push? (s/n): "
    if /i not "%continuar%"=="s" (
        echo Operacion cancelada.
        pause
        exit /b 0
    )
) else (
    echo [ADVERTENCIA] No hay repositorio remoto configurado.
    echo.
    echo Para configurar el repositorio remoto, ejecuta:
    echo   git remote add origin URL_DE_TU_REPOSITORIO
    echo.
    echo Ejemplo:
    echo   git remote add origin https://github.com/TU_USUARIO/InventarioInteligente.git
    echo.
    set /p continuar="¿Deseas continuar solo con el commit local? (s/n): "
    if /i not "%continuar%"=="s" (
        echo Operacion cancelada.
        pause
        exit /b 0
    )
)

echo.
echo [INFO] Agregando archivos al staging...
git add .

echo.
echo [INFO] Estado de los archivos:
git status --short

echo.
set /p mensaje="Ingresa el mensaje del commit (o presiona Enter para usar el mensaje por defecto): "
if "%mensaje%"=="" (
    set mensaje=Initial commit: Sistema de Inventario Inteligente
)

echo.
echo [INFO] Haciendo commit...
git commit -m "%mensaje%"

if %errorlevel% neq 0 (
    echo [ERROR] Error al hacer commit
    pause
    exit /b 1
)

echo.
echo [OK] Commit realizado exitosamente
echo.

REM Intentar hacer push si hay un remoto configurado
git remote -v >nul 2>&1
if %errorlevel% equ 0 (
    echo [INFO] Intentando subir cambios al repositorio remoto...
    git push -u origin main 2>nul
    if %errorlevel% neq 0 (
        git push -u origin master 2>nul
        if %errorlevel% neq 0 (
            echo [ADVERTENCIA] No se pudo hacer push automaticamente.
            echo Por favor ejecuta manualmente:
            echo   git push -u origin main
            echo   o
            echo   git push -u origin master
        ) else (
            echo [OK] Cambios subidos exitosamente a la rama master
        )
    ) else (
        echo [OK] Cambios subidos exitosamente a la rama main
    )
) else (
    echo [INFO] No hay repositorio remoto configurado. Solo se hizo commit local.
    echo Para configurar y subir, ejecuta:
    echo   git remote add origin URL_DE_TU_REPOSITORIO
    echo   git push -u origin main
)

echo.
echo ================================================
echo   PROCESO COMPLETADO
echo ================================================
echo.
pause
