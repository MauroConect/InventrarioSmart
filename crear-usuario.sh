#!/bin/bash
# Script para crear usuario administrador

echo "👤 Creando usuario administrador..."
echo ""

docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
    php -r "
    require '/var/www/vendor/autoload.php';
    \$app = require_once '/var/www/bootstrap/app.php';
    \$kernel = \$app->make(\Illuminate\Contracts\Console\Kernel::class);
    \$kernel->bootstrap();
    
    try {
        \$user = \App\Models\User::where('email', 'admin@inventario.com')->first();
        
        if (\$user) {
            echo '⚠️  El usuario admin@inventario.com ya existe\n';
            echo '   ¿Deseas actualizar la contraseña? (s/n): ';
            \$handle = fopen('php://stdin', 'r');
            \$line = trim(fgets(\$handle));
            fclose(\$handle);
            
            if (strtolower(\$line) === 's') {
                \$user->password = \Illuminate\Support\Facades\Hash::make('password123');
                \$user->save();
                echo '✅ Contraseña actualizada\n';
            } else {
                echo '   Usuario no modificado\n';
                exit(0);
            }
        } else {
            echo '📝 Creando nuevo usuario...\n';
            \$user = \App\Models\User::create([
                'name' => 'Administrador',
                'email' => 'admin@inventario.com',
                'password' => \Illuminate\Support\Facades\Hash::make('password123')
            ]);
            echo '✅ Usuario creado exitosamente (ID: ' . \$user->id . ')\n';
        }
        
        // Verificar que la contraseña funciona
        if (\Illuminate\Support\Facades\Hash::check('password123', \$user->password)) {
            echo '✅ Verificación de contraseña exitosa\n';
        }
        
        echo '\n';
        echo '═══════════════════════════════════════════════════════════\n';
        echo '📋 Credenciales de acceso:\n';
        echo '   Email: admin@inventario.com\n';
        echo '   Contraseña: password123\n';
        echo '═══════════════════════════════════════════════════════════\n';
    } catch (\Exception \$e) {
        echo '❌ Error: ' . \$e->getMessage() . '\n';
        exit(1);
    }
    "
