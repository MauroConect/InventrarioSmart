# 🔧 Cómo Usar Tinker

## ⚠️ Importante

**Tinker** es una consola interactiva de PHP que se ejecuta DENTRO del contenedor Docker. No puedes ejecutar código PHP directamente en PowerShell.

## 📋 Pasos Correctos

### 1. Entrar a Tinker
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan tinker
```

Verás algo como:
```
Psy Shell v0.12.18 (PHP 8.2.30 — cli) by Justin Hileman
>
```

### 2. Ejecutar Código PHP (dentro de tinker)

Ahora SÍ puedes ejecutar código PHP:

```php
// Ver todos los usuarios
\App\Models\User::all()

// Buscar un usuario
\App\Models\User::where('email', 'admin@inventario.com')->first()

// Contar usuarios
\App\Models\User::count()

// Crear un usuario
\App\Models\User::create([
    'name' => 'Administrador',
    'email' => 'admin@inventario.com',
    'password' => bcrypt('password123')
])
```

### 3. Salir de Tinker
```php
exit
```

O presiona `Ctrl+C`

## ❌ Errores Comunes

### Error: Ejecutar código PHP en PowerShell
```powershell
# ❌ INCORRECTO (en PowerShell)
\App\Models\User::all()
```

**Solución**: Primero entra a tinker, luego ejecuta el código.

### Error: Ejecutar comandos artisan en tinker
```php
// ❌ INCORRECTO (dentro de tinker)
php artisan config:clear
```

**Solución**: Sal de tinker (`exit`) y ejecuta el comando directamente:
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan config:clear
```

## ✅ Ejemplos Correctos

### Verificar usuarios
```bash
# 1. Entrar a tinker
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan tinker

# 2. Dentro de tinker, ejecutar:
\App\Models\User::all(['id', 'name', 'email'])

# 3. Salir
exit
```

### Crear usuario
```bash
# 1. Entrar a tinker
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan tinker

# 2. Dentro de tinker, ejecutar:
\App\Models\User::create([
    'name' => 'Administrador',
    'email' => 'admin@inventario.com',
    'password' => bcrypt('password123')
])

# 3. Salir
exit
```

### Limpiar cachés (NO en tinker)
```bash
# Directamente, sin entrar a tinker:
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan config:clear
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan cache:clear
```

## 📝 Resumen

- **Tinker** = Para código PHP (modelos, consultas, etc.)
- **Comandos artisan** = Fuera de tinker, directamente en la terminal
- **PowerShell** = Solo para comandos de Docker y sistema
