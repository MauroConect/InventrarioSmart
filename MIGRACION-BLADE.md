# Migración a Blade - Monolito

## ✅ Cambios Realizados

### 1. Estructura de Vistas
- ✅ Layout base (`resources/views/layouts/app.blade.php`) con Alpine.js y Tailwind CSS
- ✅ Vista de Login (`resources/views/auth/login.blade.php`)
- ✅ Vista de Dashboard (`resources/views/dashboard.blade.php`)

### 2. Controladores Web
- ✅ `App\Http\Controllers\Web\AuthController` - Autenticación con sesiones
- ✅ `App\Http\Controllers\Web\DashboardController` - Dashboard

### 3. Rutas Web
- ✅ Rutas de autenticación (login/logout)
- ✅ Rutas protegidas con middleware `auth`
- ✅ Placeholder routes para todas las páginas

### 4. Docker Simplificado
- ✅ `Dockerfile.simple` - Sin Node.js, solo PHP
- ✅ `docker-compose.simple.yml` - Configuración simplificada

## 🚀 Cómo Usar

### Opción 1: Usar Docker Simplificado (Recomendado)

```bash
# Construir y levantar
docker-compose -f docker-compose.simple.yml up -d --build

# Instalar dependencias
docker-compose -f docker-compose.simple.yml exec app composer install --no-dev

# Configurar Laravel
docker-compose -f docker-compose.simple.yml exec app php artisan key:generate
docker-compose -f docker-compose.simple.yml exec app php artisan migrate --force
docker-compose -f docker-compose.simple.yml exec app php artisan storage:link
```

### Opción 2: Continuar con Docker Actual

El código Blade funciona con el Docker actual, solo que no necesitas compilar assets de React.

## 📝 Próximos Pasos

1. **Convertir páginas restantes a Blade:**
   - Categorías
   - Productos
   - Proveedores
   - Clientes
   - Cajas
   - Cuentas Corrientes
   - Deudas Clientes
   - Movimientos Stock
   - Ventas
   - Cheques
   - Aumento Masivo Precios

2. **Crear controladores web para cada módulo**

3. **Implementar formularios con Alpine.js**

4. **Mantener las APIs existentes** (se pueden usar desde Blade con Axios)

## 🔧 Configuración de Autenticación

La autenticación ahora usa sesiones web de Laravel en lugar de tokens API. Esto es más simple y seguro para un monolito.

Las APIs siguen funcionando con tokens para uso futuro si es necesario.

## 📦 Dependencias

- **Alpine.js**: Para interactividad (CDN)
- **Tailwind CSS**: Para estilos (CDN)
- **Chart.js**: Para gráficos (CDN)
- **Axios**: Para llamadas API (CDN)

No se necesita Node.js ni npm para compilar assets.
