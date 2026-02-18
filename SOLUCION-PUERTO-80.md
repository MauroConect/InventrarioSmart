# Solución: Puerto 80 ya en uso

## Problema
```
ERROR: failed to bind host port for 0.0.0.0:80: address already in use
```

El puerto 80 está siendo usado por otro servicio (probablemente nginx, apache, o otro contenedor Docker).

## Solución 1: Identificar qué está usando el puerto 80

```bash
# Ver qué proceso está usando el puerto 80
sudo lsof -i :80
# O
sudo netstat -tulpn | grep :80
# O
sudo ss -tulpn | grep :80
```

## Solución 2: Cambiar el puerto en docker-compose.prod.yml (Rápido)

Si no necesitas usar el puerto 80, cambia a otro puerto:

```yaml
webserver:
  ports:
    - "8080:80"  # Cambiar 80 por 8080
    - "443:443"
```

Luego la aplicación estará en `http://localhost:8080`

## Solución 3: Detener el servicio que usa el puerto 80

### Si es nginx del sistema:
```bash
sudo systemctl stop nginx
# O deshabilitarlo permanentemente
sudo systemctl disable nginx
```

### Si es apache:
```bash
sudo systemctl stop apache2
# O
sudo systemctl stop httpd
```

### Si es otro contenedor Docker:
```bash
# Ver contenedores usando el puerto
docker ps | grep 80

# Detener el contenedor
docker stop <nombre_contenedor>
```

## Solución 4: Usar Nginx del sistema como Proxy (Recomendado para Producción)

Si ya tienes nginx instalado, úsalo como proxy reverso en lugar de exponer el puerto directamente.

### 1. Cambiar docker-compose.prod.yml para NO exponer el puerto 80:

```yaml
webserver:
  ports:
    # - "80:80"  # Comentar esta línea
    - "8000:80"  # Usar puerto interno
    - "443:443"
```

### 2. Configurar Nginx del sistema como proxy:

Crear archivo `/etc/nginx/sites-available/inventario`:

```nginx
server {
    listen 80;
    server_name tudominio.com;  # Cambiar por tu dominio

    location / {
        proxy_pass http://localhost:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        
        # Para WebSockets (si los usas)
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
    }
}
```

### 3. Habilitar el sitio:

```bash
sudo ln -s /etc/nginx/sites-available/inventario /etc/nginx/sites-enabled/
sudo nginx -t  # Verificar configuración
sudo systemctl reload nginx
```

## Solución 5: Usar solo el contenedor interno (Sin exponer puertos)

Si ya tienes un servidor web externo, puedes no exponer puertos y usar solo el contenedor interno:

```yaml
webserver:
  ports: []  # No exponer puertos
  # El contenedor seguirá funcionando internamente
```

Y configurar tu servidor web externo para que apunte a `inventario_webserver:80` dentro de la red Docker.

## Verificación

Después de aplicar una solución:

```bash
# Verificar que el puerto esté libre
sudo lsof -i :80

# Levantar contenedores
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d

# Verificar que funcionen
docker-compose -f docker-compose.yml -f docker-compose.prod.yml ps
```

## Recomendación

Para producción, la **Solución 4** (usar Nginx del sistema como proxy) es la mejor opción porque:
- Permite usar SSL/HTTPS fácilmente con Let's Encrypt
- Mejor control de logs y seguridad
- Puedes tener múltiples aplicaciones en el mismo servidor
- Mejor rendimiento
