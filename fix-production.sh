#!/bin/bash
# Script para corregir el entorno de producción

echo "🔧 Corrigiendo entorno de producción..."
echo ""

# 1. Instalar dependencias de Composer
echo "📦 Paso 1/5: Instalando dependencias de PHP (Composer)..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

if [ $? -ne 0 ]; then
    echo "❌ Error al instalar dependencias de Composer"
    exit 1
fi

echo "✅ Dependencias instaladas"
echo ""

# 2. Generar clave de aplicación si no existe
echo "🔑 Paso 2/5: Verificando clave de aplicación..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan key:generate --force 2>/dev/null || echo "Clave ya existe"

echo ""

# 3. Ejecutar migraciones
echo "🗄️  Paso 3/5: Ejecutando migraciones de base de datos..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan migrate --force

if [ $? -ne 0 ]; then
    echo "❌ Error al ejecutar migraciones"
    exit 1
fi

echo "✅ Migraciones ejecutadas"
echo ""

# 4. Crear enlace simbólico de storage
echo "📁 Paso 4/5: Creando enlace simbólico de storage..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan storage:link || echo "Enlace ya existe"

echo ""

# 5. Crear usuario administrador
echo "👤 Paso 5/5: Creando usuario administrador..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php artisan tinker --execute="
        \$user = \App\Models\User::where('email', 'admin@inventario.com')->first();
        if (!\$user) {
            \App\Models\User::create([
                'name' => 'Administrador',
                'email' => 'admin@inventario.com',
                'password' => bcrypt('password123')
            ]);
            echo 'Usuario creado exitosamente';
        } else {
            echo 'El usuario ya existe';
        }
    "

echo ""
echo "✅ Proceso completado!"
echo ""
echo "📝 Credenciales de acceso:"
echo "   Email: admin@inventario.com"
echo "   Contraseña: password123"
echo ""
echo "⚠️  IMPORTANTE: Cambia la contraseña después del primer inicio de sesión"
