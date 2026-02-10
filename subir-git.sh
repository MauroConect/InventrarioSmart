#!/bin/bash

# Script para subir el proyecto a Git
# Uso: ./subir-git.sh

echo ""
echo "================================================"
echo "  SUBIR PROYECTO A GIT"
echo "================================================"
echo ""

# Verificar si Git está instalado
if ! command -v git &> /dev/null; then
    echo "[ERROR] Git no está instalado."
    echo "Por favor instala Git desde: https://git-scm.com/downloads"
    exit 1
fi

echo "[OK] Git está instalado"
echo ""

# Verificar si ya hay un repositorio remoto configurado
if git remote -v &> /dev/null; then
    echo "[INFO] Repositorio remoto ya configurado:"
    git remote -v
    echo ""
    read -p "¿Deseas continuar con el commit y push? (s/n): " continuar
    if [[ ! "$continuar" =~ ^[Ss]$ ]]; then
        echo "Operación cancelada."
        exit 0
    fi
else
    echo "[ADVERTENCIA] No hay repositorio remoto configurado."
    echo ""
    echo "Para configurar el repositorio remoto, ejecuta:"
    echo "  git remote add origin URL_DE_TU_REPOSITORIO"
    echo ""
    echo "Ejemplo:"
    echo "  git remote add origin https://github.com/TU_USUARIO/InventarioInteligente.git"
    echo ""
    read -p "¿Deseas continuar solo con el commit local? (s/n): " continuar
    if [[ ! "$continuar" =~ ^[Ss]$ ]]; then
        echo "Operación cancelada."
        exit 0
    fi
fi

echo ""
echo "[INFO] Agregando archivos al staging..."
git add .

echo ""
echo "[INFO] Estado de los archivos:"
git status --short

echo ""
read -p "Ingresa el mensaje del commit (o presiona Enter para usar el mensaje por defecto): " mensaje
if [ -z "$mensaje" ]; then
    mensaje="Initial commit: Sistema de Inventario Inteligente"
fi

echo ""
echo "[INFO] Haciendo commit..."
git commit -m "$mensaje"

if [ $? -ne 0 ]; then
    echo "[ERROR] Error al hacer commit"
    exit 1
fi

echo ""
echo "[OK] Commit realizado exitosamente"
echo ""

# Intentar hacer push si hay un remoto configurado
if git remote -v &> /dev/null; then
    echo "[INFO] Intentando subir cambios al repositorio remoto..."
    if git push -u origin main 2>/dev/null; then
        echo "[OK] Cambios subidos exitosamente a la rama main"
    elif git push -u origin master 2>/dev/null; then
        echo "[OK] Cambios subidos exitosamente a la rama master"
    else
        echo "[ADVERTENCIA] No se pudo hacer push automáticamente."
        echo "Por favor ejecuta manualmente:"
        echo "  git push -u origin main"
        echo "  o"
        echo "  git push -u origin master"
    fi
else
    echo "[INFO] No hay repositorio remoto configurado. Solo se hizo commit local."
    echo "Para configurar y subir, ejecuta:"
    echo "  git remote add origin URL_DE_TU_REPOSITORIO"
    echo "  git push -u origin main"
fi

echo ""
echo "================================================"
echo "  PROCESO COMPLETADO"
echo "================================================"
echo ""
