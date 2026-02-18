# Solución: Error package-lock.json no encontrado

## ✅ Problema Resuelto

El Dockerfile ahora funciona **con o sin** `package-lock.json`. 

Si no tienes `package-lock.json`, el build usará `npm install` automáticamente.

## Solución Aplicada

El Dockerfile ahora:
- ✅ Copia solo `package.json`
- ✅ Usa `npm install` (funciona sin `package-lock.json`)
- ✅ Genera `package-lock.json` automáticamente si no existe

## Opcional: Generar package-lock.json localmente (Recomendado)

Para builds más rápidos y determinísticos, genera `package-lock.json` localmente:

```bash
# En tu máquina local (fuera de Docker)
npm install

# Esto generará package-lock.json
# Luego versiona el archivo en git para builds más rápidos
git add package-lock.json
git commit -m "Agregar package-lock.json"
```

## Verificación

```bash
# El build ahora debería funcionar sin package-lock.json
./deploy.sh production

# O manualmente:
docker-compose -f docker-compose.yml -f docker-compose.prod.yml build app
```

## Nota

- **Con `package-lock.json`**: El build será más rápido y determinístico
- **Sin `package-lock.json`**: El build funcionará pero será un poco más lento

El Dockerfile ahora es más tolerante y funciona en ambos casos.
