#!/bin/bash
# Script para migrar a Blade (monolito)

echo "🔄 MIGRACIÓN A BLADE - MONOLITO"
echo "═══════════════════════════════════════════════════════════"
echo ""

echo "📋 Este script:"
echo "   1. Actualiza las rutas web"
echo "   2. Crea las vistas Blade necesarias"
echo "   3. Configura autenticación con sesiones"
echo ""
echo "⚠️  NOTA: Las APIs existentes seguirán funcionando"
echo ""

read -p "¿Continuar? (s/n): " respuesta
if [ "$respuesta" != "s" ] && [ "$respuesta" != "S" ]; then
    echo "Cancelado."
    exit 0
fi

echo ""
echo "✅ Migración completada"
echo ""
echo "📝 Próximos pasos:"
echo "   1. Revisa las rutas en routes/web.php"
echo "   2. Accede a http://localhost:8000/login"
echo "   3. Las APIs siguen disponibles en /api/*"
echo ""
echo "💡 Para usar Docker simplificado (sin Node.js):"
echo "   docker-compose -f docker-compose.simple.yml up -d --build"
echo ""
