#!/bin/bash
# Script auxiliar para el build de Docker
# Genera package-lock.json si no existe

if [ ! -f package-lock.json ]; then
    echo "⚠️  package-lock.json no encontrado. Generándolo..."
    npm install --package-lock-only
    echo "✅ package-lock.json generado"
fi
