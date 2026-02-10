# 🏪 Sistema de Control de Inventario Inteligente

Sistema completo de gestión de inventario desarrollado con **Laravel** (Backend) y **React** (Frontend), dockerizado para fácil despliegue.

[![Laravel](https://img.shields.io/badge/Laravel-10.x-red.svg)](https://laravel.com)
[![React](https://img.shields.io/badge/React-18.x-blue.svg)](https://reactjs.org)
[![Docker](https://img.shields.io/badge/Docker-Ready-blue.svg)](https://www.docker.com)
[![PHP](https://img.shields.io/badge/PHP-8.2-purple.svg)](https://www.php.net)

## Características

- ✅ **Apertura y Cierre de Caja**: Control completo de cajas con apertura y cierre diario
- ✅ **Control de Stock**: Gestión de inventario con movimientos de entrada, salida y ajustes
- ✅ **Gestión de Clientes**: CRUD completo de clientes
- ✅ **Cuenta Corriente**: Control de cuentas corrientes con movimientos
- ✅ **Deudas de Clientes**: Gestión de deudas con seguimiento de pagos
- ✅ **Proveedores**: Administración de proveedores
- ✅ **Categorías de Productos**: Organización por categorías
- ✅ **Ventas**: Sistema completo de ventas con múltiples formas de pago
- ✅ **Dashboard Interactivo**: Gráficos y estadísticas en tiempo real
- ✅ **Gestión de Cheques**: Control de cheques recibidos y emitidos
- ✅ **Reportes**: Visualización de datos con gráficos interactivos

## Requisitos

- Docker
- Docker Compose

## Instalación

1. Clonar el repositorio:
```bash
git clone <repository-url>
cd InventarioInteligente
```

2. Copiar el archivo de entorno:
```bash
cp .env.example .env
```

3. Construir y levantar los contenedores:
```bash
docker-compose up -d --build
```

4. Instalar dependencias de PHP:
```bash
docker-compose exec app composer install
```

5. Generar clave de aplicación:
```bash
docker-compose exec app php artisan key:generate
```

6. Ejecutar migraciones:
```bash
docker-compose exec app php artisan migrate
```

7. Instalar dependencias de Node.js:
```bash
docker-compose exec app npm install
```

8. Compilar assets:
```bash
docker-compose exec app npm run build
```

## Configuración

Editar el archivo `.env` con las configuraciones necesarias:

```env
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=inventario_db
DB_USERNAME=inventario_user
DB_PASSWORD=root
```

## Uso

Una vez levantados los contenedores, acceder a:
- Frontend: http://localhost:8000
- Base de datos: localhost:3306

## Comandos útiles

- Ver logs: `docker-compose logs -f`
- Detener contenedores: `docker-compose down`
- Reiniciar contenedores: `docker-compose restart`
- Ejecutar comandos artisan: `docker-compose exec app php artisan <comando>`

## Estructura del Proyecto

```
InventarioInteligente/
├── app/
│   ├── Http/Controllers/  # Controladores API
│   └── Models/            # Modelos Eloquent
├── database/
│   └── migrations/        # Migraciones de base de datos
├── resources/
│   ├── js/
│   │   ├── components/    # Componentes React
│   │   ├── pages/         # Páginas React
│   │   └── context/       # Contextos React
│   └── views/             # Vistas Blade
├── routes/
│   ├── api.php           # Rutas API
│   └── web.php           # Rutas Web
└── docker/               # Configuración Docker
```

## API Endpoints

### Autenticación
- `POST /api/login` - Iniciar sesión
- `POST /api/logout` - Cerrar sesión
- `GET /api/user` - Usuario actual

### Categorías
- `GET /api/categorias` - Listar categorías
- `POST /api/categorias` - Crear categoría
- `PUT /api/categorias/{id}` - Actualizar categoría
- `DELETE /api/categorias/{id}` - Eliminar categoría

### Productos
- `GET /api/productos` - Listar productos
- `POST /api/productos` - Crear producto
- `PUT /api/productos/{id}` - Actualizar producto
- `DELETE /api/productos/{id}` - Eliminar producto

### Clientes
- `GET /api/clientes` - Listar clientes
- `POST /api/clientes` - Crear cliente
- `PUT /api/clientes/{id}` - Actualizar cliente
- `DELETE /api/clientes/{id}` - Eliminar cliente

### Cajas
- `GET /api/cajas` - Listar cajas
- `POST /api/cajas` - Abrir caja
- `POST /api/cajas/{id}/cerrar` - Cerrar caja

### Ventas
- `GET /api/ventas` - Listar ventas
- `POST /api/ventas` - Crear venta

## Desarrollo

Para desarrollo con hot-reload:

```bash
docker-compose exec app npm run dev
```

## Licencia

MIT
