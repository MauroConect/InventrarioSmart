# Solución: Conflicto de Versiones Vite

## Problema
```
npm error ERESOLVE unable to resolve dependency tree
npm error Found: vite@7.3.1
npm error Could not resolve dependency:
npm error peer vite@"^5.0.0 || ^6.0.0" from laravel-vite-plugin@1.3.0
```

## Causa
- `laravel-vite-plugin@1.3.0` requiere `vite@^5.0.0 || ^6.0.0`
- Pero npm está instalando `vite@7.3.1` (versión más reciente)
- Hay un conflicto de versiones

## Solución Aplicada

### 1. Fijar versión de Vite a 5.x
Se actualizó `package.json` para usar una versión específica de Vite 5.x:
```json
"vite": "5.4.11"
```

### 2. Usar --legacy-peer-deps en Dockerfile
Se agregó el flag `--legacy-peer-deps` al comando npm install en el Dockerfile:
```dockerfile
RUN npm install --prefer-offline --no-audit --progress=false --legacy-peer-deps
```

## Verificación

Después de estos cambios, el build debería funcionar:

```bash
# Reconstruir sin cache
docker-compose -f docker-compose.yml -f docker-compose.prod.yml build --no-cache app

# O desplegar completo
./deploy-prod.sh
```

## Alternativa: Actualizar laravel-vite-plugin

Si prefieres usar Vite 7.x, necesitarías actualizar `laravel-vite-plugin` a una versión que lo soporte:

```bash
npm install laravel-vite-plugin@latest --save-dev
```

Pero esto puede requerir cambios en la configuración. La solución actual (fijar Vite 5.x) es más segura y estable.

## Nota

Si el problema persiste, elimina `node_modules` y `package-lock.json` localmente y regenera:

```bash
rm -rf node_modules package-lock.json
npm install
```

Luego vuelve a intentar el build de Docker.
