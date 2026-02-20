<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

echo "🔍 Verificando conexión a la base de datos...\n";

try {
    DB::connection()->getPdo();
    echo "✅ Conexión a la base de datos exitosa\n\n";
} catch (\Exception $e) {
    echo "❌ Error de conexión a la base de datos: " . $e->getMessage() . "\n";
    exit(1);
}

echo "🔍 Verificando si el usuario ya existe...\n";
$user = User::where('email', 'admin@inventario.com')->first();

if ($user) {
    echo "⚠️  El usuario admin@inventario.com ya existe\n";
    echo "   ID: {$user->id}\n";
    echo "   Nombre: {$user->name}\n";
    echo "   Email: {$user->email}\n";
    echo "   Creado: {$user->created_at}\n\n";
    
    echo "🔄 Actualizando contraseña del usuario existente...\n";
    $user->password = Hash::make('password123');
    $user->save();
    echo "✅ Contraseña actualizada correctamente\n\n";
} else {
    echo "👤 Creando nuevo usuario administrador...\n";
    
    try {
        $user = User::create([
            'name' => 'Administrador',
            'email' => 'admin@inventario.com',
            'password' => Hash::make('password123'),
        ]);
        
        echo "✅ Usuario creado exitosamente\n";
        echo "   ID: {$user->id}\n";
        echo "   Nombre: {$user->name}\n";
        echo "   Email: {$user->email}\n\n";
    } catch (\Exception $e) {
        echo "❌ Error al crear usuario: " . $e->getMessage() . "\n";
        exit(1);
    }
}

echo "🔐 Verificando que la contraseña funciona...\n";
$testUser = User::where('email', 'admin@inventario.com')->first();
if (Hash::check('password123', $testUser->password)) {
    echo "✅ La contraseña se verifica correctamente\n\n";
} else {
    echo "❌ Error: La contraseña no se verifica correctamente\n";
    echo "   Esto puede indicar un problema con el hashing\n\n";
}

echo "📋 Resumen:\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "   Email: admin@inventario.com\n";
echo "   Contraseña: password123\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "\n✅ Proceso completado. Ahora puedes iniciar sesión.\n";
