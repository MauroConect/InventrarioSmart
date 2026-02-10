# 🔧 Solución: Errores de Timeout en Composer

## Problemas Identificados

1. **Timeout de Composer:** El proceso excedió los 300 segundos (5 minutos)
2. **Errores de Conexión:** `curl error 28` - Connection timeout al descargar desde GitHub

## Soluciones Aplicadas

### 1. ✅ Script Actualizado (`iniciar-proyecto.bat`)
- Aumentado timeout a 600 segundos (10 minutos)
- Agregado flag `--prefer-dist` para descargas más rápidas
- Reintento automático si falla la primera vez

### 2. ✅ Script Alternativo (`instalar-composer-manual.bat`)
Script dedicado con manejo mejorado de errores:
- Limpia cache antes de instalar
- Timeout aumentado a 600 segundos
- Múltiples intentos con diferentes opciones
- Instrucciones claras de solución de problemas

### 3. ✅ Dockerfile Mejorado
- Configuración global de Composer con timeout aumentado
- Configuración para preferir descargas distribuidas (más rápidas)

## Cómo Usar

### Opción 1: Usar el Script Principal (Recomendado)
```cmd
iniciar-proyecto.bat
```
Ahora tiene timeout aumentado y reintentos automáticos.

### Opción 2: Instalación Manual de Composer
Si el script principal falla, usa:
```cmd
instalar-composer-manual.bat
```

### Opción 3: Comando Manual
Si prefieres hacerlo manualmente:
```cmd
docker-compose exec app composer config --global process-timeout 600
docker-compose exec app composer install --prefer-dist --no-interaction
```

## Si el Problema Persiste

### 1. Verificar Conexión a Internet
```cmd
ping github.com
```

### 2. Limpiar Cache de Composer
```cmd
docker-compose exec app composer clear-cache
```

### 3. Instalar con Más Tolerancia a Errores
```cmd
docker-compose exec app composer install --prefer-dist --ignore-platform-reqs --no-scripts
docker-compose exec app composer dump-autoload --optimize
```

### 4. Verificar Proxy/Firewall
Si estás detrás de un proxy o firewall corporativo, configura Composer:
```cmd
docker-compose exec app composer config --global http-basic.github.com usuario token
```

### 5. Usar Mirror Alternativo
Si GitHub está muy lento, puedes usar un mirror:
```cmd
docker-compose exec app composer config --global repos.packagist composer https://packagist.org
```

## Tiempo Estimado

Con conexión normal: **5-10 minutos**
Con conexión lenta: **15-20 minutos**
Con problemas de conexión: Puede requerir múltiples intentos

## Nota Importante

El proceso de instalación de Composer puede ser lento, especialmente la primera vez. Esto es normal debido a:
- Gran cantidad de dependencias (110+ paquetes)
- Descarga desde repositorios remotos (GitHub)
- Procesamiento y verificación de paquetes

**Se paciente y no interrumpas el proceso.**
