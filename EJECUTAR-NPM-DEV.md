# 🚀 Cómo Ejecutar npm run dev

## Solución Implementada

Se corrigió el conflicto de nombres de archivos:
- ✅ `app.jsx` - Punto de entrada (con ReactDOM.render)
- ✅ `AppComponent.jsx` - Componente principal de la aplicación

## Para Ejecutar en Modo Desarrollo

### Opción 1: Usar el Script (Recomendado)
```cmd
npm-dev.bat
```

### Opción 2: Comando Manual
Abre una terminal y ejecuta:
```cmd
docker-compose exec app npm run dev
```

**IMPORTANTE:** Este comando debe mantenerse corriendo mientras desarrollas. No cierres la ventana.

## Para Compilar para Producción

Si prefieres compilar los assets una sola vez (más rápido pero sin hot-reload):
```cmd
docker-compose exec app npm run build
```

## Verificar que Funciona

Una vez que ejecutes `npm run dev`, deberías ver:
```
VITE v5.x.x ready in XXX ms
➜  Local:   http://localhost:5173/
```

Y luego puedes acceder a tu aplicación en: **http://localhost:8000**
